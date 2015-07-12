<?php

/*
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/
namespace Longman\TelegramBot\Commands;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Parse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Command;
use Longman\TelegramBot\Entities\Update;

class DefaultCommand extends Command
{
    protected $name = 'default';
    protected $description = 'Process';
    protected $usage = '<command>';
    protected $version = '1.0.0';
    protected $enabled = true;

    public function execute()
    {
        $connection = $this->getConfig('connection');
        $update = $this->getUpdate();
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
        $message_id = $message->getMessageId();
        $text = $message->getText();
        $db_chat_id = null;
        $db_token = null;
        $db_phone = null;
        $code_sent = null;
        $attempts = null;

        $sth = $this->telegram->pdo->prepare('SELECT * FROM users WHERE chat_id= :chat_id');
        $sth->bindValue(':chat_id', $chat_id);
        $sth->execute();
        $msg = '';
        if ($sth->rowCount() > 0) {
            $user = $sth->fetch(\PDO::FETCH_ASSOC);
            if (!empty($user['chat_id'])) {
                $db_chat_id = $user['chat_id'];
            }
            if (!empty($user['token'])) {
                $db_token = $user['token'];
            }
            if (!empty($user['phone'])) {
                $db_phone = $user['phone'];
            }
            if (!empty($user['code_sent'])) {
                $code_sent = $user['code_sent'];
            }
            if (!empty($user['attempts'])) {
                $attempts = $user['attempts'];
            }
        }

        $msg .= $db_chat_id . $db_phone . $db_token;

        $need_number_str = 'Для авторизации введи свой номер телефона, например 9123456789, я вышлю тебе код.' . "\n\n";
        $have_sent = 'Я выслал тебе код подтверждения. =) Напиши его мне, пожалуйста.';

        if (empty($db_chat_id)) {
            $sth = $this->telegram->pdo->prepare('INSERT INTO `users` (`chat_id`, `need_number`) VALUES (?, 1)');
            $sth->bindParam(1, $chat_id, \PDO::PARAM_STR);
            $status = $sth->execute();
            return $this->render($chat_id, $message_id, $need_number_str);
        } elseif (empty($db_phone)) {
            if (preg_match( '/^[0-9]{10}$/', $text)) {
                $sth = $this->telegram->pdo->prepare('UPDATE users SET phone = :phone WHERE chat_id= :chat_id');
                $sth->bindValue(':phone', $text);
                $sth->bindValue(':chat_id', $chat_id);
                $sth->execute();
                return $this->sendCode($chat_id, $message_id, $text, $have_sent);
            } else
            return $this->render($chat_id, $message_id, $need_number_str);
        } elseif (empty($code)) {
            if ($code_sent == 1) {
                if ($this->checkCode($text)) {
                    return $this->render($chat_id, $message_id, 'Круто! Теперь ты можешь писать мне. И я все сохраню. Ищи их на SaveBox.pro');
                }
                if ($attempts <= 0) {
                    return $this->render($chat_id, $message_id, 'Извини, но у тебя больше нет попыток авторизации=(');
                }
                $attempts = $attempts-1;
                $sth = $this->telegram->pdo->prepare('UPDATE users SET attempts = :attempts WHERE chat_id= :chat_id');
                $sth->bindValue(':attempts', $attempts);
                $sth->bindValue(':chat_id', $chat_id);
                $sth->execute();
                return $this->render($chat_id, $message_id, 'Ой, это не правильный код. Осталось попыток: '.$attempts);
            } else return $this->sendCode($chat_id, $message_id, $db_phone, $have_sent);
        }

        $commands = $this->telegram->getCommandsList();
        //    if (empty($text)) {
        $msg .= 'Привет! Я SaveBoх и сделаю все' . "\n\n";

        /*  } else {
              $text = str_replace('/', '', $text);
              if (isset($commands[$text])) {
                  $command = $commands[$text];
                  $msg = 'Command: ' . $command->getName() . ' v' . $command->getVersion() . "\n";
                  $msg.= 'Description: ' . $command->getDescription() . "\n";
                  $msg.= 'Usage: ' . $command->getUsage();
              } else {
                  $msg = 'Command ' . $text . ' not found';
              }
          } */

        return $this->render($chat_id, $message_id, $msg);
    }

    private function render($chat_id, $message_id, $msg) {
        $data = array();
        $data['chat_id'] = $chat_id;
        $data['reply_to_message_id'] = $message_id;
        $data['text'] = $msg;

        $result = Request::sendMessage($data);
        return $result;
    }

    protected function sendCode($chat_id, $message_id, $phone, $text) {
        $response = Parse::send('functions/sendCode', ['phoneNumber' => $phone]);
        if (isset($response->error)) {
            // заменить ноер телефона
        }
        $sth = $this->telegram->pdo->prepare('UPDATE users SET code_sent = 1 WHERE chat_id= :chat_id');
        $sth->bindValue(':chat_id', $chat_id);
        $sth->execute();
        return $this->render($chat_id, $message_id, $text);
    }

    protected function checkCode($text) {
        return false;
    }
}
