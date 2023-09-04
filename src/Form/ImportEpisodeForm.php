<?php declare(strict_types = 1);

namespace Drupal\rick_and_morty\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Rick and Morty form.
 */
final class ImportEpisodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rick_and_morty_import_episode';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = \Drupal::config('rick_and_morty.settings')->get();
    $endpoint = $config['api_url'] . $config['api_url_episodes_endpoint'];
    $indexed_count = rick_and_morty_node_load_by_name('episode');
    $total_count = rick_and_morty_get_total_items($endpoint);

    $form['index_progress'] = [
      '#theme' => 'progress_bar',
      '#percent' => $total_count ? (int) (100 * $indexed_count / $total_count) : 100,
      '#message' => $this->t('@indexed/@total indexed', ['@indexed' => $indexed_count, '@total' => $total_count]),
    ];

    $form['import_episode'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import Episodes'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = \Drupal::config('rick_and_morty.settings')->get();
    $endpoint = $config['api_url'] . $config['api_url_episodes_endpoint'] . '?page=';
    $total_pages = (int)$config['api_url_episodes_total_pages'];

    $endpoints = array_map(function ($page) use ($endpoint) {
      return $endpoint . $page;
    }, range(1, $total_pages));

    $client = \Drupal::httpClient();

    $promises = [];
    foreach ($endpoints as $url) {
      $promises[] = $client->getAsync($url);
    }

    // Wait for all promises to complete.
    $responses = \GuzzleHttp\Promise\Utils::unwrap($promises);

    foreach ($responses as $response) {
      // Process each response as needed.
      $statusCode = $response->getStatusCode();
      $content = $response->getBody()->getContents();
      $data = json_decode($content, TRUE)['results'];
      foreach ($data as $data) {
        $operations[] = ['import_episodes_data', [$data]];
      }
    }

    $batch = [
      'title' => $this->t('Importing episodes ...'),
      'operations' => $operations,
      'init_message' => t('Importing'),
      'progress_message' => t('Processed @current out of @total.'),
      'finished' => 'import_episodes_data_finished',
    ];

    batch_set($batch);
  }

}
