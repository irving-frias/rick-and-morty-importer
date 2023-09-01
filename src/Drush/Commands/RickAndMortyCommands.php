<?php

namespace Drupal\rick_and_morty\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Datetime\DrupalDateTime;

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
    $endpoint = $config['api_url'] . $config['api_url_characters_endpoint'];
    $total_pages = (int)$config['api_url_characters_total_pages'];
    $typeOfData = 'character';

    foreach ($this->fetchDataGenerator($endpoint, $total_pages) as $item) {
        foreach ($this->fetchSingleDataGenerator($item, $typeOfData) as $item) {
            echo $item . PHP_EOL;
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
    $endpoint = $config['api_url'] . $config['api_url_locations_endpoint'];
    $total_pages = (int)$config['api_url_locations_total_pages'];
    $typeOfData = 'location';

    foreach ($this->fetchDataGenerator($endpoint, $total_pages) as $item) {
        foreach ($this->fetchSingleDataGenerator($item, $typeOfData) as $item) {
            echo $item . PHP_EOL;
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
    $typeOfData = 'episode';

    foreach ($this->fetchDataGenerator($endpoint, $total_pages) as $item) {
        foreach ($this->fetchSingleDataGenerator($item, $typeOfData) as $item) {
            echo $item . PHP_EOL;
        }
    }

    $this->logger()->success(dt('All episodes imported sucessfully.'));
  }

  private function getConfigFormSettings() {
    $config = $this->configFactory->getEditable('rick_and_morty.settings');
    $settings = $config->get();

    return $settings;
  }

  private function fetchDataGenerator($endpoint, $total_pages) {
      // Simulate fetching data from a source.
      $endpoint = $endpoint . '?page=';

      $data = array_map(function ($page) use ($endpoint) {
          return $endpoint . $page;
      }, range(1, $total_pages));

      foreach ($data as $item) {
          yield $this->fetchData($item); // Yield each item one at a time.
      }
  }

  private function fetchData(string $endpoint) {
    $response = \Drupal::httpClient()->get($endpoint);
    if ($response->getStatusCode() != 200) {
        return [];
    }

    $body = $response->getBody();
    $jsonString = $body->getContents(); // Convert the stream to a string.
    $data = json_decode($jsonString, TRUE);

    return $data['results'];
  }

  private function fetchSingleDataGenerator($data, $typeOfData) {
    foreach ($data as $item) {
        yield $this->fetchSingleData($item, $typeOfData); // Yield each item one at a time.
    }
  }

  private function fetchSingleData($data, $typeOfData) {
    switch ($typeOfData) {
      case 'character':
        $this->createCharacter($data, $typeOfData);
        break;

      case 'location':
        $this->createLocation($data);
        break;

      case 'episode':
        $this->createEpisode($data);
        break;
    }
  }

  private function createCharacter(&$data, &$typeOfData) {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $mediaStorage = $this->entityTypeManager->getStorage('media');
    $nids = $nodeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', $typeOfData, 'IN')
        ->condition('field_character_id', $data['id'], 'IN')
        ->execute();
    $mid = $mediaStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'character', 'IN')
        ->condition('name', $data['name'], 'IN')
        ->execute();

    if (empty($mid)) {
        $image_data = file_get_contents($data['image']);
        $file_repository = \Drupal::service('file.repository');
        $processed_name = strtolower(str_replace(' ', '-', $data['name']));
        $image = $file_repository->writeData($image_data, "public://" . $processed_name . ".png", FileSystemInterface::EXISTS_REPLACE);

        $image_media = Media::create([
          'name' => $data['name'],
          'bundle' => 'character',
          'field_media_image' => [
              'target_id' => $image->id(),
              'alt' => $data['name'],
              'title' => $data['name'],
          ],
          'uid' => 1,
        ]);

        $image_media->save();
    } else {
        $mids = $mediaStorage->loadMultiple($mid);
        $image_media = array_shift($mids);
    }

    $date = new \DateTime($data['created']);
    $date = $date->format('Y-m-d');

    if (empty($nids)) {
        $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->create([
            'type' => $typeOfData, // Replace with your content type machine name.
            'title' => $data['name'],
            'uid' => 1,
            'field_character_created' => [
                'value' => $date,
            ],
        ]);

        $node->get('field_character_gender')->setValue(rick_and_morty_taxonomy_load_by_name('character_gender', $data['gender'])->id());
        $node->get('field_character_id')->setValue($data['id']);
        $node->get('field_character_image')->setValue($image_media);
        $node->get('field_character_location')->setValue(rick_and_morty_taxonomy_load_by_name('character_location', $data['location']['name'])->id());
        $node->get('field_character_name')->setValue($data['name']);
        $node->get('field_character_species')->setValue(rick_and_morty_taxonomy_load_by_name('character_species', $data['species'])->id());
        $node->get('field_character_status')->setValue(rick_and_morty_taxonomy_load_by_name('character_status', $data['status'])->id());
        $node->get('field_character_type')->setValue(rick_and_morty_taxonomy_load_by_name('character_type', $data['type'])->id());

        // Save the node.
        $node->save();
    } else {
        $nodes = $nodeStorage->loadMultiple($nids);
        $node = array_shift($nodes);
        $node->field_character_created->value = $date;
        $node->field_character_gender = rick_and_morty_taxonomy_load_by_name('character_gender', $data['gender'])->id();
        $node->field_character_id = $data['id'];
        $node->field_character_image = $image_media;
        $node->field_character_location = rick_and_morty_taxonomy_load_by_name('character_location', $data['location']['name'])->id();
        $node->field_character_name = $data['name'];
        $node->field_character_species = rick_and_morty_taxonomy_load_by_name('character_species', $data['species'])->id();
        $node->field_character_status = rick_and_morty_taxonomy_load_by_name('character_status', $data['status'])->id();
        $node->field_character_type = rick_and_morty_taxonomy_load_by_name('character_type', $data['type'])->id();
        $node->save();
    }

    $this->logger()->success(dt('Character imported: ' . $data['id'] . ' ' . $data['name']));
  }

  private function createLocation(&$data) {
    $this->logger()->success(dt('Location imported: ' . $data['id'] . ' ' . $data['name']));
  }

  private function createEpisode(&$data) {
    $this->logger()->success(dt('Episode imported: ' . $data['id'] . ' ' . $data['name']));
  }
}
