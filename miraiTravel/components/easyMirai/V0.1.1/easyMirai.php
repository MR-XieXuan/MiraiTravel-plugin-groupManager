<?php

namespace MiraiTravel\Components\easyMirai\V0_1_1;

use MiraiTravel\Components\Component;
use MiraiTravel\LogSystem\LogSystem;
use MiraiTravel\MessageChain\MessageChain;

class easyMirai extends Component
{
    static $focus = false;

    /**
     * 用以下方式对函数进行闭包
     * 闭包以后通过hook函数进行挂钩
     * 这样做是为了代码日后的可维护性
     * 如果全部均在 hook 函数内进行闭包并且挂钩到 QQBot 对象内。
     * 那么必将导致 hook 函数内部混乱 影响日后的维护
     */
    /**
     * @param array     $message    回复的消息链 
     * @param bool|int  $quote      回复注重的消息或者其他消息 
     */
    function init()
    {
        return true;
    }

    function hook()
    {
        /**
         * 设置专注的会话
         */
        $this->_qqBot->set_focus = function ($message) {
            $logSystem = new LogSystem($this->_qqBot->get_qq(), "QQBot");
            $logSystem->write_log("Script", "set_focus", json_encode($message));
            $this->_qqBot->focus = $message;
        };

        /**
         * 回复消息
         */
        $this->_qqBot->reply_message = function ($message, $quote = false) {
            $logSystem = new LogSystem($this->_qqBot->get_qq(), "QQBot");

            // 如果是字符串的话就转成只有字符串的 messageChain 
            if (gettype($message) === "string") {
                $messageChain = new MessageChain;
                $messageChain->push_plain($message);
                $message = $messageChain->get_message_chain();
            }

            if ($quote === true) {
                $quote = $this->_qqBot->focus['messageChain'][0]['id'];
            }
            switch ($this->_qqBot->focus['type']) {
                case "FriendMessage":
                    $logSystem->write_log("script", "reply_message", $this->_qqBot->focus['sender']['id'] . " For FriendMessage " . json_encode($message));
                    $this->_qqBot->send_friend_massage($this->_qqBot->focus['sender']['id'], $message, $quote);
                    break;
                case "GroupMessage":
                    $logSystem->write_log("script", "reply_message", $this->_qqBot->focus['sender']['group']['id'] . " For GroupMessage " . json_encode($message));
                    $this->_qqBot->send_group_massage($this->_qqBot->focus['sender']['group']['id'], $message, $quote);
                    break;
                case "TempMessage":
                    $logSystem->write_log("script", "reply_message", $this->_qqBot->focus['sender']['id'] . "||" . $this->_qqBot->focus['sender']['group']['id'] . " For TempMessage " . json_encode($message));
                    $this->_qqBot->send_temp_massage($this->_qqBot->focus['sender']['group']['id'], $this->_qqBot->focus['sender']['group']['id'], $message, $quote);
                    break;
                case "StrangerMessage":
                    $logSystem->write_log("script", "reply_message", "回复陌生人消息方法待开发。");
                    break;
                default:
                    $logSystem->write_log("script", "reply_message", "你似乎使用了错误的方法,此方法仅用于回复 好友消息 , 群消息 , 临时消息 , 模式人消息。");
            }
        };

        /**
         * 群管理
         */
        $this->_qqBot->reply_mute = function ($number = true) {
            // 如果没有参数的话就全体禁言
            if ($number === true) {
                return $this->_qqBot->mute_all($this->_qqBot->focus['sender']['group']['id']);
            }
            return $this->_qqBot->mute($this->_qqBot->focus['sender']['group']['id'], $number);
        };

        $this->_qqBot->reply_unmute = function ($number = true) {
            // 如果没有参数的话就全体禁言
            if ($number === true) {
                $this->_qqBot->unmute_all($this->_qqBot->focus['sender']['group']['id']);
            }
        };

        /**
         * 同意请求
         */
        $this->_qqBot;
    }
}
