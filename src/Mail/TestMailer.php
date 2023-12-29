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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tobento\Service\Mail\MailerInterface;
use Tobento\Service\Mail\RendererInterface;
use Tobento\Service\Mail\QueueHandlerInterface;
use Tobento\Service\Mail\MessageInterface;
use Tobento\Service\Mail\ParameterInterface;
use Tobento\Service\Mail\Parameter;
use Tobento\Service\Mail\TemplateInterface;
use Tobento\Service\Mail\Template;
use Tobento\Service\Mail\TemplateMessage;
use Tobento\Service\Mail\Symfony\HtmlToTextConverter;
use Tobento\Service\Mail\Event;
use Tobento\Service\Mail\MailerException;
use Symfony\Component\Mime\Email;
use Closure;

final class TestMailer implements MailerInterface
{
    private array $messages = [];
    
    /**
     * Create a new Mailer.
     *
     * @param string $name
     * @param null|RendererInterface $renderer
     */
    public function __construct(
        private string $name,
        private null|RendererInterface $renderer = null,
    ) {}
    
    /**
     * Returns the mailer name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Send one or multiple message(s).
     *
     * @param MessageInterface ...$message
     * @return void
     * @throws MailerException
     */
    public function send(MessageInterface ...$message): void
    {
        foreach($message as $msg) {
            $this->sendMessage($msg);
        }
    }

    /**
     * Sends the message.
     *
     * @param MessageInterface $message
     * @return void
     * @throws MailerException
     */
    private function sendMessage(MessageInterface $message): void
    {
        $this->messages[] = $this->renderMessage($message);
    }
    
    private function renderMessage(MessageInterface $message): MessageInterface
    {
        if (is_null($this->renderer)) {
            return $message;
        }
        
        if ($message->getHtml() instanceof TemplateInterface) {
            
            $data = $message->getHtml()->data();
            
            $data['message'] = new TemplateMessage($message->getSubject());
            
            $template = new Template(
                name: $message->getHtml()->name(),
                data: $data
            );
            
            $html = $this->renderer->renderTemplate($template);
            $message->html($html);
        }
        
        if ($message->getText() instanceof TemplateInterface) {
            $text = $this->renderer->renderTemplate($message->getText());
            $message->text($text);
        }
        
        // create text from html:
        if (is_null($message->getText()) && is_string($message->getHtml())) {
            $text = (new HtmlToTextConverter)->convert($message->getHtml());            
            $message->text($text);
        }
        
        return $message;
    }
    
    public function sent(string $message): TestMessage
    {
        $messages = array_filter($this->messages, static function (MessageInterface $m) use ($message): bool {
            return $m instanceof $message;
        });
        
        TestCase::assertTrue(
            count($messages) > 0,
            sprintf('The expected [%s] message was not sent.', $message)
        );
        
        return new TestMessage($message, $messages);
    }
}