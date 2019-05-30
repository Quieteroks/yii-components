<?php

namespace quieteroks\components\mail;

use yii\base\InvalidConfigException;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

/**
 * Base mail message.
 *
 * For example:
 *
 * ```php
 * SubscribeMessage::create()->send();
 * // or
 * SubscribeMessage::create()
 *      ->compose()
 *      ->attach($fileName)
 *      ->send();
 * ```
 */
abstract class Message
{
    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * Constructor for base mail message.
     *
     * @param MailerInterface $mailer
     */
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * HTML view path for render message.
     *
     * @return string
     */
    abstract protected function getHtmlView(): string;

    /**
     * Text view path for render message without html.
     *
     * @return string
     */
    abstract protected function getTextView(): string;

    /**
     * Subject for the message.
     *
     * @return string
     */
    abstract protected function getSubject(): string;

    /**
     * Send the message.
     *
     * @param string $email
     * @param array $params
     * @return bool
     */
    public function send(string $email, array $params = []): bool
    {
        return $this->compose($email, $params)->send();
    }

    /**
     * Compose the mail message for send.
     *
     * @param string $email
     * @param array $params
     * @return MessageInterface
     */
    public function compose(string $email, array $params = []): MessageInterface
    {
        $params = array_merge_recursive(
            $this->getRequiredParams(), $params
        );
        return $this->mailer
            ->compose($this->getMessageView(), $params)
            ->setSubject($this->getSubject())
            ->setTo($email);
    }

    /**
     * Returns the required params for all message.
     *
     * @return array
     */
    protected function getRequiredParams(): array
    {
        return [];
    }

    /**
     * Returns the message views templates.
     *
     * @return array|string
     */
    protected function getMessageView()
    {
        $views = array_filter([
            'html' => $this->getHtmlView(),
            'text' => $this->getTextView(),
        ]);
        if (count($views) == 1) {
            return array_shift($views);
        }
        return $views;
    }

    /**
     * Create the message with params.
     *
     * @param array $params
     * @return Message
     * @throws InvalidConfigException
     */
    public static function create(array $params = []): self
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return \Yii::createObject(get_called_class(), $params);
    }
}
