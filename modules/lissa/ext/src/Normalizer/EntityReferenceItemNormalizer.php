<?php

/**
 * @file
 * Contains \Drupal\ext\Normalizer\EntityReferenceItemNormalizer.
 */

namespace Drupal\ext\Normalizer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;

/**
 * Converts the Drupal entity reference item object to HAL array structure.
 */
class EntityReferenceItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem';

  /**
   * The hypermedia link manager.
   *
   * @var \Drupal\rest\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The entity resolver.
   *
   * @var \Drupal\serialization\EntityResolver\EntityResolverInterface
   */
  protected $entityResolver;

  /**
   * Constructs an EntityReferenceItemNormalizer object.
   *
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $entity_Resolver
   *   The entity resolver.
   */
  public function __construct(LinkManagerInterface $link_manager, EntityResolverInterface $entity_Resolver) {
    $this->linkManager = $link_manager;
    $this->entityResolver = $entity_Resolver;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function normalize($field_item, $format = NULL, array $context = array()) {
    /** @var $field_item \Drupal\Core\Field\FieldItemInterface */
    $target_entity = $field_item->get('entity')->getValue();

    // If this is not a content entity, let the parent implementation handle it,
    // only content entities are supported as embedded resources.
    if (!($target_entity instanceof ContentEntityInterface)) {
      return parent::normalize($field_item, $format, $context);
    }

    // If the parent entity passed in a langcode, unset it before normalizing
    // the target entity. Otherwise, untranslatable fields of the target entity
    // will include the langcode.
    $langcode = isset($context['langcode']) ? $context['langcode'] : NULL;
    unset($context['langcode']);

    // Normalize the target entity.
    $embedded = $this->serializer->normalize($target_entity, $format, $context);
    // If the field is translatable, add the langcode to the link relation
    // object. This does not indicate the language of the target entity.
    if ($langcode) {
      $embedded['lang'] = $langcode;
    }

    $field = $field_item->getParent();
    return array(
      $field->getName() => array($embedded),
    );
  }

  /**
   * Overrides \Drupal\ext\Normalizer\FieldItemNormalizer::constructValue().
   */
  protected function constructValue($data, $context) {
    $field_item = $context['target_instance'];
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    if ($id = $this->entityResolver->resolve($this, $data, $target_type)) {
      return array('target_id' => $id);
    }
    return NULL;
  }

  /**
   * Implements \Drupal\serialization\EntityResolver\UuidReferenceInterface::getUuid().
   */
  public function getUuid($data) {
    if (isset($data['uuid'])) {
      $uuid = $data['uuid'];
      if (is_array($uuid)) {
        $uuid = reset($uuid);
      }
      return $uuid;
    }
  }

}