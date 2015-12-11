<?php
/**
 * Phergie plugin for Pull excuses from bastard operator from hell (phergie-irc-plugin-react-bofh)
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-devans for the canonical source repository
 * @copyright Copyright (c) 2015 Joe Ferguson (http://www.joeferguson.me)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\DEVANS
 */

namespace Phergie\Irc\Plugin\React\DEVANS;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEventInterface as Event;
//use WyriHaximus\Phergie\Plugin\Http\Request;
use Phergie\Plugin\Http\Request;
use WyriHaximus\Phergie\Plugin\Url\Url;

/**
 * Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\DEVANS
 */
class Plugin extends AbstractPlugin
{
    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {

    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'command.devans' => 'handleDevansCommand',
            'command.devans.help' => 'handleDevansHelpCommand',
        ];
    }

    /*
    * @param \Phergie\Irc\Plugin\React\Command\CommandEventInterface $event
    * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
    */
    public function handleDevansCommand(Event $event, Queue $queue)
    {
        $this->getLogger()->info('[DEVANS] received a new command');

        $this->fetchExcuse($event, $queue);
    }

    /*
    * @param \Phergie\Irc\Plugin\React\Command\CommandEventInterface $event
    * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
    */
    public function handleDevansHelpCommand(Event $event, Queue $queue)
    {
        $messages = [
            'Usage: devans'
        ];
        foreach ($messages as $message) {
            $queue->ircPrivmsg($event->getSource(), $message);
        }
    }

    public function fetchExcuse($event, $queue)
    {
        $url = 'http://devanswers.ru';

        $request = new Request([
            'url' => $url,
            'resolveCallback' =>
                function ($data, $headers, $code) use ($event, $queue) {

                    $dom = new \DOMDocument();
                    $dom->loadHTML($data);
                    $xpath = new \DOMXpath($dom);
                    // XPath to the excuse text
                    $result = $xpath->query('/html/head/script[6]');

                    if ($result->length > 0) {
//                        $queue->ircPrivmsg($event->getSource(), $result->item(0)->nodeValue);
                        $queue->ircPrivmsg($event->getSource(), print_r($result));
                    }

                    if ($data->getStatusCode() !== 200) {
                        $this->getLogger()->notice('[DEVANS] Site responded with error', [
                            'code' => $data->getStatusCode(),
                            /*'message' => $data['error']['message'],*/
//                            'message' => var_dump($data->getStatusCode),
                            'message' => $data->getReasonPhrase(),
                        ]);
                        $queue->ircPrivmsg($event->getSource(), 'Sorry, no excuse was found');
                        return;
                    }
                    $this->getLogger()->info('[DEVANS] Site successful return');
                },
            'rejectCallback' =>
                function ($data, $headers, $code) use ($event, $queue) {
                    $this->getLogger()->notice('[DEVANS] Site failed to respond');
                    $queue->ircPrivmsg($event->getSource(), 'Sorry, there was a problem communicating with the site');
                },
        ]);
        $this->getEventEmitter()->emit('http.request', [$request]);
    }
}
