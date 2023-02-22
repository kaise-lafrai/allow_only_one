<?php

namespace Drupal\allow_only_one\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Plugin implementation of the 'AllowOnlyOne' field type.
 *
 * @FieldType(
 *   id = "allow_only_one",
 *   label = @Translation("Allow Only One"),
 *   category = @Translation("Validation"),
 *   description = @Translation("This field stores configuration that is used during validation to determine the uniqueness of a node/term"),
 *   default_widget = "allow_only_one_widget",
 *   default_formatter = "allow_only_one"
 * )
 */
class AllowOnlyOneFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('unused value'))
      ->setDescription(t('unused value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    // This field should store nothing on the node but Drupal must have a col.
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'size' => 'tiny',
          'description' => "Not needed but required by Drupal.",
        ],
      ],
    ];
  }

  /**
   * Grabs fields that are part of content type.
   *
   * @param mixed $field
   *   The allow only one field object.
   *
   * @return array
   *   Returning the fields that are part of the content type.
   */
  protected function getContentTypeFields($field) {

    $contentType = $field->getTargetBundle();
    $entity_type = $field->getTargetEntityTypeId();
    $entityManager = \Drupal::service('entity_field.manager');
    $fields = [];

    if (!empty($contentType)) {
      $fields = array_filter(
        $entityManager->getFieldDefinitions($entity_type, $contentType),
            function ($field_definition) {
              return $field_definition instanceof FieldConfigInterface;
            }
      );
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();

    if ($field->getType() === 'allow_only_one' && $field instanceof ThirdPartySettingsInterface) {
      $fields = $this->getContentTypeFields($field);
      // Add a fieldset for the validators.
      $form['allow_only_one'] = [
        '#type' => 'details',
        '#title' => 'Unique field combinations',
        '#open' => TRUE,
        '#parents' => ['third_party_settings', 'allow_only_one'],
      ];

      foreach ($fields as $key => $item) {
        if ($item->getType() != 'allow_only_one') {
          $form['allow_only_one'][$key] = [
            '#type' => 'checkbox',
            '#title' => $item->label(),
            '#description' => $this->t('Machine name: @name', ['@name' => $item->getName()]),
            '#tree' => TRUE,
            '#default_value' => $field->getThirdPartySetting('allow_only_one', $key),
          ];
        }
      }

      $form['allow_only_one']['title'] = [
        '#type' => 'checkbox',
        '#title' => 'Title',
        '#description' => $this->t('Choose case sensitivity options for Title'),
        '#tree' => TRUE,
        '#prefix' => '<div">',
        '#default_value' => $field->getThirdPartySetting('allow_only_one', 'title'),
      ];

      $form['allow_only_one']['case_sensitive'] = [
        '#type' => 'radios',
        '#options' => [
          0 => $this->t('Case Insensitive'),
          1 => $this->t('Case Sensitive'),
        ],
        '#tree' => TRUE,
        '#attributes' => ['class' => ['description']],
        '#suffix' => '</div">',
        '#default_value' => $field->getThirdPartySetting('allow_only_one', 'case_sensitive'),
        // @todo This state to show/hide the field is not working.
        '#states' => [
          'visible' => [
            ':input[name="third_party_settings[allow_only_one][title]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['allow_only_one']['limit_validation_to_published'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Limit validation to published entities'),
        '#default_value' => $field->getThirdPartySetting('allow_only_one', 'limit_validation_to_published'),
        '#description' => $this->t('If checked, allow only one logic will not apply to unpublished entities.'),
      ];

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = $this->getTypedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('AllowOnlyOne', []);
    return $constraints;
  }

}
