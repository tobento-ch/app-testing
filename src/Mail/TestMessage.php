<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Mail;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Mail\MessageInterface;
use Tobento\Service\Mail\ParameterInterface;
use Tobento\Service\Mail\Parameter;
use Tobento\Service\Mail\AddressesInterface;
use Closure;

final class TestMessage
{
    /**
     * Create a new TestMessage.
     *
     * @param string $message
     * @param array<array-key, MessageInterface> $messages
     */
    public function __construct(
        private string $message,
        private array $messages,
    ) {}
    
    private function filterMessages(Closure $callback): array
    {
        return $this->messages = array_filter(
            $this->messages,
            static function (MessageInterface $m) use ($callback): bool {
                return $callback($m);
            }
        );
    }
    
    public function assertFrom(string $email, null|string $name = null): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $m->getFrom()?->email() === $email && $m->getFrom()?->name() === $name
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message was not sent from: %s<%s> address.',
                $this->message,
                is_null($name) ? '' : $name.' ',
                $email
            )
        );

        return $this;
    }
    
    public function assertHasTo(string $email, null|string $name = null): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $this->hasAddress($m->getTo(), $email, $name)
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message was not sent to: %s<%s> address.',
                $this->message,
                is_null($name) ? '' : $name.' ',
                $email
            )
        );

        return $this;
    }
    
    public function assertHasCc(string $email, null|string $name = null): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $this->hasAddress($m->getCc(), $email, $name)
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message has no Cc: %s<%s> address.',
                $this->message,
                is_null($name) ? '' : $name.' ',
                $email
            )
        );

        return $this;
    }
    
    public function assertHasBcc(string $email, null|string $name = null): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $this->hasAddress($m->getBcc(), $email, $name)
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message has no Bcc: %s<%s> address.',
                $this->message,
                is_null($name) ? '' : $name.' ',
                $email
            )
        );

        return $this;
    }
    
    public function assertReplyTo(string $email, null|string $name = null): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $m->getReplyTo()?->email() === $email && $m->getReplyTo()?->name() === $name
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message has no replyTo: %s<%s> address.',
                $this->message,
                is_null($name) ? '' : $name.' ',
                $email
            )
        );

        return $this;
    }
    
    public function assertSubject(string $subject): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => $m->getSubject() === $subject
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message has no subject: %s',
                $this->message,
                $subject
            )
        );

        return $this;
    }
    
    public function assertTextContains(string $text): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => is_string($m->getText()) && str_contains($m->getText(), $text)
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message text does not contain: %s',
                $this->message,
                $text
            )
        );

        return $this;
    }
    
    public function assertHtmlContains(string $html): static
    {
        $messages = $this->filterMessages(
            fn (MessageInterface $m) => is_string($m->getHtml()) && str_contains($m->getHtml(), $html)
        );

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message html does not contain: %s',
                $this->message,
                $html
            )
        );

        return $this;
    }
    
    public function assertHasParameter(string $name, null|Closure $callback = null): static
    {
        $messages = $this->filterMessages(function (MessageInterface $m) use ($name, $callback): bool {
            $params = $m->parameters()->filter(fn(ParameterInterface $p): bool => $p instanceof $name);
            
            if (is_null($params->first())) {
                return false;
            }
            
            if ($callback) {
                $params = $m->parameters()->filter($callback);
            }
            
            return count($params->all()) > 0 ? true : false;
        });        

        TestCase::assertTrue(
            count($messages) > 0,
            sprintf(
                'The expected [%s] message has no parameter: %s',
                $this->message,
                $name
            )
        );

        return $this;
    }
    
    public function assertIsQueued(): static
    {
        $this->assertHasParameter(Parameter\Queue::class);
            
        return $this;
    }
    
    public function assertTimes(int $times): static
    {
        TestCase::assertCount(
            $times,
            $this->messages,
            sprintf(
                'The expected [%s] message was sent %d times instead of %d times.',
                $this->message,
                count($this->messages),
                $times
            )
        );

        return $this;
    }
    
    private function hasAddress(AddressesInterface $addresses, string $email, null|string $name = null): bool
    {
        foreach($addresses as $address) {
            if ($address->email() === $email && $address->name() === $name) {
                return true;
            }
        }

        return false;
    }
}