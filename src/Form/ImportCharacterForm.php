<?php declare(strict_types = 1);

namespace Drupal\rick_and_morty\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Rick and Morty form.
 */
final class ImportCharacterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rick_and_morty_import_character';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['import_character'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import Characters'),
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
    $endpoint = $config['api_url'] . $config['api_url_characters_endpoint'] . '?page=';
    $total_pages = (int)$config['api_url_characters_total_pages'];

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
        $operations[] = ['import_characters_data', [$data]];
      }
    }

    $batch = [
        'title' => $this->t('Importing characters ...'),
        'operations' => $operations,
        'init_message' => t('Importing'),
        'progress_message' => t('Processed @current out of @total.'),
        'finished' => 'import_characters_data_finished',
    ];

    batch_set($batch);
  }

}
