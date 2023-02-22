<?php

namespace Drupal\allow_only_one\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the uniqueness of content based on a combination of field values.
 */
class AllowOnlyOneConstraintValidator extends ConstraintValidator {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($fielditems, Constraint $constraint) {
    $entity = $fielditems->getEntity();
    $entity_type = $entity->getEntityTypeId();

    $allow_only_one_field_settings = $fielditems->getFieldDefinition()->getThirdPartySettings('allow_only_one');
    if (empty($allow_only_one_field_settings) || !$this->entityTypeAllowed($entity_type)) {
      // This covers cases on field settings save where this validation runs
      // without settings, because they don't exist.
      return;
    }

    $field_values = [];
    $field_labels = [];
    $enforce_title_case_sensitivity = $this->getCaseSensitiveSetting($allow_only_one_field_settings);
    $enforce_unique_title = $this->getTitleSetting($allow_only_one_field_settings);
    if ($enforce_unique_title) {
      $name = ($entity_type === 'taxonomy_term') ? 'name' : 'title';
      $field_values[$name] = $entity->label();
      $sensitive = ($enforce_title_case_sensitivity) ? $this->t('case sensitive') : $this->t('not case sensitive');
      $field_labels[$name] = ucfirst($name) . " {{$sensitive}}";
    }

    $limit_validation_to_published = $this->getLimitPublishedSetting($allow_only_one_field_settings);

    foreach ($allow_only_one_field_settings as $field_setting_name => $field_setting) {
      if ($field_setting === 1) {
        // Seems like some risk here that getString may return giberish.
        $field_value = trim($entity->get($field_setting_name)->getString());
        if (!empty($field_value)) {
          $field_values[$field_setting_name] = $field_value;
          $field_labels[] = $entity->$field_setting_name->getFieldDefinition()->getLabel();
        }
      }
    }

    switch ($entity_type) {
      case 'node':
        $entity_bundle = $entity->getType();
        $bundle_property = 'type';
        $id_property = 'nid';

        break;

      case 'taxonomy_term':
        $entity_bundle = $entity->bundle();
        $bundle_property = 'vid';
        $id_property = 'tid';
        break;

    }

    $matching_entities = $this->getEntityMatches(
      $field_values,
      ($enforce_unique_title) ? $entity->label() : NULL,
      $enforce_title_case_sensitivity,
      $limit_validation_to_published,
      $entity_type,
      $bundle_property,
      $entity_bundle,
      $id_property
    );

    if (!empty($matching_entities)) {
      foreach ($matching_entities as $entity) {
        $url = $entity->toUrl()->toString();;
        $title = $entity->label();

        $this->context->buildViolation($constraint->entityMessage)
          ->setParameter(':url', $url)
          ->setParameter('@title', $title)
          ->setParameter('%label', implode(', ', $field_labels))
          ->atPath('value')
          ->addViolation();
      }
    }

  }

  /**
   * Obtains entities that match the field value combination.
   *
   * @param array $unique_field_key_values
   *   An array of field names and value pairs to look up.
   * @param string $title
   *   The content title or NULL if title is not used for query.
   * @param bool $title_case_sensitivity
   *   The case sensitivity setting.
   * @param bool $limit_to_published
   *   The limit validation to published entities setting.
   * @param string $entity_type
   *   The entity type (node or taxonomy_term).
   * @param string $bundle_property
   *   The field bundle.
   * @param string $entity_bundle
   *   The entity bundle.
   * @param string $id_property
   *   The property that contains the entity id (nid or tid).
   *
   * @return array
   *   Return array of entities that match.
   */
  private function getEntityMatches(
      array $unique_field_key_values,
      $title,
      $title_case_sensitivity,
      $limit_to_published,
      $entity_type,
      $bundle_property,
      $entity_bundle,
      $id_property
    ) {
    // Skip match lookup if entity is unpublished and limit to published is true.
    $entity_status = $this->context->getRoot()->getEntity()->status->value;
    if (!$entity_status && $limit_to_published) {
      return [];
    }
    if ($entity_type && $unique_field_key_values && $bundle_property && $entity_bundle) {
      $query = \Drupal::entityQuery($entity_type)
        ->condition($bundle_property, $entity_bundle)
        ->range(0, 1);
      foreach ($unique_field_key_values as $field => $value) {
        $query->condition($field, $value);
      }
      if ($title) {
        $title_label = ($entity_type === 'taxonomy_term') ? 'name' : 'title';
        $like_search = ($title_case_sensitivity) ? 'LIKE BINARY' : 'LIKE';
        $query->condition($title_label, $title, $like_search);
      }
      // Exclude the current entity.
      if (!empty($id = $this->context->getRoot()->getEntity()->id())) {
        $query->condition($id_property, $id, '!=');
      }
      // Check setting limiting to published entities.
      if ($limit_to_published) {
        $query->condition('status', 1);
      }
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        $entities = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($entity_ids);
        return $entities;
      }
    }
    return [];
  }

  /**
   * Checks to see if the entity type is allowed.
   *
   * @param string $type
   *   The type of entity.
   *
   * @return bool
   *   TRUE if allowed, FALSE otherwise.
   */
  protected function entityTypeAllowed(string $type) : bool {
    $allowed = ['node', 'taxonomy_term'];
    return (bool) in_array($type, $allowed);
  }

  /**
   * Gets case sensitivity setting and removes it from other field settings.
   *
   * @param array $allow_only_one_field_settings
   *   Array of field level settings.
   *
   * @return bool
   *   TRUE if case sensitivity should be applied to title. FALSE otherwise.
   */
  protected function getCaseSensitiveSetting(array &$allow_only_one_field_settings) : bool {
    if (!empty($allow_only_one_field_settings['title'])) {
      // Using title, so use case sensitivity setting.
      $case_sensitive_setting = $allow_only_one_field_settings['case_sensitive'];
    }
    else {
      // Not using title, so can't use case sensitivity.
      $case_sensitive_setting = FALSE;
    }
    unset($allow_only_one_field_settings['case_sensitive']);
    return (bool) $case_sensitive_setting;
  }

  /**
   * Gets title setting and removes it from other field settings.
   *
   * @param array $allow_only_one_field_settings
   *   Array of field level settings.
   *
   * @return bool
   *   TRUE if title should be included in unique comparison. FALSE otherwise.
   */
  protected function getTitleSetting(array &$allow_only_one_field_settings) : bool {
    $title_setting = $allow_only_one_field_settings['title'];
    unset($allow_only_one_field_settings['title']);
    return (bool) $title_setting;
  }

  /**
   * Gets limit to published entities setting and removes it from other field settings.
   *
   * @param array $allow_only_one_field_settings
   *   Array of field level settings.
   *
   * @return bool
   *   TRUE if limit to published is checked. FALSE otherwise.
   */
  protected function getLimitPublishedSetting(array &$allow_only_one_field_settings) : bool {
    if (array_key_exists('limit_validation_to_published', $allow_only_one_field_settings)) {
      $limit_validation_to_published = $allow_only_one_field_settings['limit_validation_to_published'];
      unset($allow_only_one_field_settings['limit_validation_to_published']);
      return (bool) $limit_validation_to_published;
    }
    return false;
  }

}
