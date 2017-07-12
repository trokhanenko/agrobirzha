<?php
 
namespace Drupal\feedback_form\Form;
 
use Drupal\Core\Form\FormBase;                   // Базовый класс Form API
use Drupal\Core\Form\FormStateInterface;              // Класс отвечает за обработку данных
use Drupal\node\Entity\Node;

 
/**
 * @see \Drupal\Core\Form\FormBase
 */
class FeedbackForm extends FormBase {
  public function getFormId() {
  	return 'feedback_form';
 }
 
	public function buildForm(array $form, FormStateInterface $form_state) {
	$config = $this->config('simple.settings');
 	$form['title'] = [
 		'#type' => 'textfield',
 		'#title' => t('Ваше Имя'),
 		'#required' => true,
 	];
 	$form['body'] = [
 	    '#type' => 'textfield',
 		'#title' => t('Ваш отзыв'),
 		'#required' => true,
 	];

 	$form['actions']['submit'] = [
 		'#type' => 'submit',
 		'#value' => $this->t('Оставить отзыв'),
 	];
 	return $form;
 }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  	$title = $form_state->getValue('title');
  	$body = $form_state->getValue('body');

  	$title = htmlspecialchars($title);
  	$body = htmlspecialchars($body);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tit = $form_state->getValue('title');
    $bod = $form_state->getValue('body');
    
    $node = Node::create(array(
      'nid' => null,
      'type' => 'feedback',
      'title' => $tit,
      'field_feed' => $bod,
      'uid' => 1,
      'status' => 0,
    ));
   $node->save();
   kint($form_state);
 
    drupal_set_message(t('Спасибо, Ваш отзыв успешно принят'));
 }

}
