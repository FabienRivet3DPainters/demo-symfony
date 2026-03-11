<?php

namespace App\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $securityLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthSuccess',
            Events::AUTHENTICATION_FAILURE => 'onAuthFailure',
        ];
    }

    public function onAuthSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        $this->securityLogger->info('JWT Authentication success', [
            'user' => $user->getUserIdentifier(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    public function onAuthFailure(AuthenticationFailureEvent $event): void
    {
        $this->securityLogger->warning('JWT Authentication failure', [
            'reason' => $event->getException()->getMessage(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
