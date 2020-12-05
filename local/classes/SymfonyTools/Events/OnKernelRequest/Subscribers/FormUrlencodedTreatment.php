<?php

namespace Local\SymfonyTools\Events\OnKernelRequest\Subscribers;

use Local\SymfonyTools\Events\OnKernelRequest\Interfaces\OnKernelRequestHandlerInterface;
use Local\SymfonyTools\Events\OnKernelRequest\Traits\AbstractSubscriberKernelRequestTrait;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class FormUrlencodedTreatment
 * @package Local\SymfonyTools\Events\OnKernelRequest\Subscribers
 *
 * @since 11.09.2020
 * @since 05.12.2020 Убрал EventSubscriberInterface, чтобы предотвратить дублирующий запуск листенера.
 */
class FormUrlencodedTreatment implements OnKernelRequestHandlerInterface
{
    use AbstractSubscriberKernelRequestTrait;

    /**
     * Событие kernel.request.
     *
     * Особое обращение с данными, прикидывающимися формой.
     *
     * @param RequestEvent $event Объект события.
     *
     * @return void
     *
     */
    public function handle(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $header = $request->headers->get('content-type');

        if ($header === 'application/x-www-form-urlencoded'
            ||
            $header === 'application/json'
        ) {
            $arPostData = (array)json_decode($request->getContent(), true);
            $arPostData = json_decode(
                json_encode($arPostData),
                true
            );

            $result = $this->arrayOfStrings($arPostData);

            $request->request->replace($result);
        }
    }

    /**
     * Рекурсивная очистка массивов
     *
     * @param array $array Массив.
     *
     * @return array OK or NULL.
     */
    protected function arrayOfStrings(array $array): array
    {
        $result = [];
        foreach ((array)$array as $key => $item) {
            $result[$key] = is_array($item) ? $this->arrayOfStrings($item) : (string)$item;
        }

        return $result;
    }
}
