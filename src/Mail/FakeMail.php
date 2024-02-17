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

use Tobento\App\Testing\FakerInterface;
use Tobento\App\AppInterface;
use Tobento\Service\Mail\MailersInterface;
use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\Mailers;

final class FakeMail implements FakerInterface
{
    /**
     * Create a new FakeMail.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {
        $app->on(
            MailersInterface::class,
            function(MailersInterface $mailers): MailersInterface {
                
                $fakeMailers = [];

                foreach($mailers->names() as $name) {
                    $fakeMailers[] = $this->createMailer($name);
                }
                
                return new Mailers(...$fakeMailers);
            }
        );
    }
    
    /**
     * Returns a new instance.
     *
     * @param AppInterface $app
     * @return static
     */
    public function new(AppInterface $app): static
    {
        return new static($app);
    }

    /**
     * Returns the mailers.
     *
     * @return MailersInterface
     */
    public function mailers(): MailersInterface
    {
        return $this->app->get(MailersInterface::class);
    }
    
    /**
     * Returns the mailer.
     *
     * @param string $name
     * @return MailerInterface
     */
    public function mailer(string $name = null): MailerInterface
    {
        return $this->app->get(MailersInterface::class)->mailer($name);
    }
    
    /**
     * Create a new mailer.
     *
     * @param string $name
     * @return MailerInterface
     */
    private function createMailer(string $name): MailerInterface
    {
        return $this->app->make(TestMailer::class, ['name' => $name]);
    }
}