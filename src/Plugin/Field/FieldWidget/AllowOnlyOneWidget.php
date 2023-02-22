<?php

namespace Drupal\allow_only_one\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'AllowOnlyOne' widget.
 *
 * @FieldWidget(
 *   id = "allow_only_one_widget",
 *   module = "allow_only_one",
 *   label = @Translation("Allow Only One widget"),
 *   field_types = {
 *     "allow_only_one"
 *   }
 * )
 */
class AllowOnlyOneWidget extends WidgetBase {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Only including this for parity with schema. Otherwise an error appears.
    $fieldname = $items->getName();
    $fieldname = str_replace('_', '-', $fieldname);

    $element['value'] = [
      '#type' => 'item',
      // Markup is needed so the error message link can target it.
      '#markup' => "<span id=\"edit-{$fieldname}-0\"></span>",
      '#disabled' => TRUE,
      '#value' => 0,
    ];

    return $element;
  }

}
