<?php
/**
 * @file
 * Contains \Drupal\ulogin\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\ulogin\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Does something.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
//    TODO Need convert to const
    if ($request->attributes->get('_maintenance') == 4
      && \Drupal::currentUser()
        ->isAnonymous() && $request->getPathInfo() == 'ulogin'
    ) {
//         Allow access to ulogin path even if site is in offline mode.
      $request->attributes->set('_maintenance', 5);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 35);
    return $events;
  }

}