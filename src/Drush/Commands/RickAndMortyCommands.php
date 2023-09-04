<?php

namespace Drupal\rick_and_morty\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
final class RickAndMortyCommands extends DrushCommands {
    /**
    * The entity type manager service.
    *
    * @var \Drupal\Core\Entity\EntityTypeManagerInterface
    */
    protected $entityTypeManager;

    /**
    * The configuration factory.
    *
    * @var \Drupal\Core\Config\ConfigFactoryInterface
    */
    protected $configFactory;

  /**
   * Constructs a RickAndMortyCommands object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct();
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Imports all characters from rick and morty api.
   */
  #[CLI\Command(name: 'rick_and_morty:import-characters', aliases: ['rnmic'])]
  #[CLI\Usage(name: 'rick_and_morty:import-characters rnmic', description: 'Usage description')]
  public function importCharacters() {
    $config = $this->getConfigFormSettings();
    $total_pages = (int)$config['api_url_characters_total_pages'];
    $endpoint = $config['api_url'] . $config['api_url_characters_endpoint'] . '?page=';

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
        $node = create_or_update_characters_node($data);
        $node->save();
        $this->logger()->notice(dt('Character @name imported sucessfully.', ['@name' => $data['name']]));
      }
    }

    $this->logger()->success(dt('All characters imported sucessfully.'));
  }

  /**
   * Imports all locations from rick and morty api.
   */
  #[CLI\Command(name: 'rick_and_morty:import-locations', aliases: ['rnmil'])]
  #[CLI\Usage(name: 'rick_and_morty:import-locations rnmil', description: 'Usage description')]
  public function importLocations() {
    $config = $this->getConfigFormSettings();
    $total_pages = (int)$config['api_url_locations_total_pages'];
    $endpoint = $config['api_url'] . $config['api_url_locations_endpoint'] . '?page=';

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
        $node = create_or_update_locations_node($data);
        $node->save();
        $this->logger()->notice(dt('Location @name imported sucessfully.', ['@name' => $data['name']]));
      }
    }

    $this->logger()->success(dt('All locations imported sucessfully.'));
  }

  /**
   * Imports all episodes from rick and morty api.
   */
  #[CLI\Command(name: 'rick_and_morty:import-episodes', aliases: ['rnmie'])]
  #[CLI\Usage(name: 'rick_and_morty:import-episodes rnmie', description: 'Usage description')]
  public function importEpisodes() {
    $config = $this->getConfigFormSettings();
    $endpoint = $config['api_url'] . $config['api_url_episodes_endpoint'];
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
        $node = create_or_update_episodes_node($data);
        $node->save();
        $this->logger()->notice(dt('Episode @name imported sucessfully.', ['@name' => $data['name']]));
      }
    }

    $this->logger()->success(dt('All episodes imported sucessfully.'));
  }

  private function getConfigFormSettings() {
    $config = $this->configFactory->getEditable('rick_and_morty.settings');
    $settings = $config->get();

    return $settings;
  }

}
