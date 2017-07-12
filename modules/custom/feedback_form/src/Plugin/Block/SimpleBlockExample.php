<?php

/**
 * @file
 * Contains \Drupal\feedback_form\Plugin\Block\SimpleBlockExample.
 */

// Пространство имён для нашего блока.
// helloworld - это наш модуль.
namespace Drupal\feedback_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Добавляем простой блок с текстом.
 * Ниже - аннотация, она также обязательна.
 *
 * @Block(
 *   id = "simple_block_example",
 *   admin_label = @Translation("Simple block example"),
 * )
 */
class SimpleBlockExample extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\feedback_form\Form\FeedbackForm');
    return $form;
  }

}