<?php declare(strict_types = 1);

namespace Drupal\rick_and_morty\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Rick and Morty settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'rick_and_morty_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['rick_and_morty.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $endpoint = 'https://rickandmortyapi.com/api';
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#default_value' => $endpoint,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $character_endpoint = '/character';
    $form['api_url_characters_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Characters Endpoint'),
      '#default_value' => $character_endpoint,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $totalPages = $this->getTotalPages($endpoint . $character_endpoint);

    $form['api_url_characters_total_pages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Characters Total Pages'),
      '#default_value' => $totalPages,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $location_endpoint = '/location';
    $form['api_url_locations_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locations Endpoint'),
      '#default_value' => $location_endpoint,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $totalPages = $this->getTotalPages($endpoint . $location_endpoint);

    $form['api_url_locations_total_pages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locations Total Pages'),
      '#default_value' => $totalPages,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $episodes_endpoint = '/episode';
    $form['api_url_episodes_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Episodes Endpoint'),
      '#default_value' => $episodes_endpoint,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    $totalPages = $this->getTotalPages($endpoint . $episodes_endpoint);

    $form['api_url_episodes_total_pages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locations Episodes Pages'),
      '#default_value' => $totalPages,
      '#attributes' => ['readonly' => 'readonly'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('rick_and_morty.settings')
      ->set('api_url', $form_state->getValue('api_url'))
      ->save();

    $this->config('rick_and_morty.settings')
    ->set('api_url_characters_endpoint', $form_state->getValue('api_url_characters_endpoint'))
    ->save();

    $this->config('rick_and_morty.settings')
      ->set('api_url_locations_endpoint', $form_state->getValue('api_url_locations_endpoint'))
      ->save();

    $this->config('rick_and_morty.settings')
    ->set('api_url_episodes_endpoint', $form_state->getValue('api_url_episodes_endpoint'))
    ->save();

    $this->config('rick_and_morty.settings')
    ->set('api_url_characters_total_pages', $form_state->getValue('api_url_characters_total_pages'))
    ->save();

    $this->config('rick_and_morty.settings')
      ->set('api_url_locations_total_pages', $form_state->getValue('api_url_locations_total_pages'))
      ->save();

    $this->config('rick_and_morty.settings')
    ->set('api_url_episodes_total_pages', $form_state->getValue('api_url_episodes_total_pages'))
    ->save();
    parent::submitForm($form, $form_state);
  }

  private function getTotalPages(string $endpoint): int {
    $response = \Drupal::httpClient()->get($endpoint);
    if ($response->getStatusCode() != 200) {
        return [];
    }

    $body = $response->getBody();
    $jsonString = $body->getContents(); // Convert the stream to a string.
    $data = json_decode($jsonString, TRUE);

    return $data['info']['pages'];
  }
}
