<?php
/*
 * (c) Antonny Cyrille <rewieer@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Local\Bundles\TaskSchedulerBundle\Event;

class EventDispatcher {
  /**
   * @var EventSubscriberInterface[]
   */
  private $subscribers = [];

  /**
   * Adds a subscriber
   * @param EventSubscriberInterface $eventSubscriber
   */
  public function addSubscriber(EventSubscriberInterface $eventSubscriber) {
    $this->subscribers[] = $eventSubscriber;
  }

  /**
   * @return EventSubscriberInterface[]
   */
  public function getSubscribers() {
    return $this->subscribers;
  }

  /**
   * Dispatches the event
   * @param string $event
   * @param array $args
   */
  public function dispatch(string $event, $args = []) {
    foreach($this->subscribers as $subscriber) {
      $events = $subscriber->getEvents();
      $keys = array_keys($events);

      if (in_array($event, $keys)) {
        call_user_func_array([$subscriber, $events[$event]], $args);
      }
    }
  }
}