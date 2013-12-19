<?php

/**
 * Facula Framework Struct Manage Unit
 *
 * Facula Framework 2013 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\Unit\SMTP;

class Datar
{
    private $mail = array();
    private $mailContent = array();
    private $parsedMail = array();

    public function __construct(array $mail)
    {
        global $_SERVER;
        $senderHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        $appInfo = \Facula\Framework::getVersion();

        $checkContent = '';

        $mailContent = array(
            'Title' => isset($mail['Title']) ? $mail['Title'] : null,
            'Message' => isset($mail['Message']) ? $mail['Message'] : null,
        );

        // Parse mail body
        $mail['Subject'] = '=?UTF-8?B?' . rtrim(base64_encode($mailContent['Title'] ? $mailContent['Title'] : 'Untitled'), '=') . '?=';
        $mail['Body'] = chunk_split(base64_encode($mailContent['Message']) . '?=', 76, "\r\n");
        $mail['AltBody'] = chunk_split(base64_encode(strip_tags(str_replace('</', "\r\n</", $mailContent['Message']))) . '?=', 76, "\r\n");

        // Make mail header
        $this->addLine('MIME-Version', '1.0');
        $this->addLine('X-Priority', '3');
        $this->addLine('X-MSMail-Priority', 'Normal');

        $this->addLine('X-Mailer', $appInfo['App'] . ' ' . $appInfo['Ver'] . ' (' . $appInfo['Base'] . ')');
        $this->addLine('X-MimeOLE', $appInfo['Base'] . ' Mailer OLE');

        $this->addLine('X-AntiAbuse', 'This header was added to track abuse, please include it with any abuse report');
        $this->addLine('X-AntiAbuse', 'Primary Hostname - ' .$senderHost);
        $this->addLine('X-AntiAbuse', 'Original Domain - ' . $senderHost);
        $this->addLine('X-AntiAbuse', 'Originator/Caller UID/GID - [' . $senderHost . ' ' . $mail['SenderIP'] . '] / [' . $senderHost . ' ' . $mail['SenderIP'] . ']');
        $this->addLine('X-AntiAbuse', 'Sender Address Domain - ' . $senderHost);

        // Mail title
        $this->addLine('Subject', $mail['Subject']);

        // Addresses
        $this->addLine('From', '=?UTF-8?B?' . base64_encode($mail['Screenname']) . '?= <' . $mail['From'] . '>');
        $this->addLine('To', 'undisclosed-recipients:;');
        $this->addLine('Return-Path', '<' . $mail['ReturnTo'] . '>');
        $this->addLine('Reply-To', '<' . $mail['ReplyTo'] . '>');
        $this->addLine('Errors-To', '<' . $mail['ErrorTo'] . '>');

        $this->addLine('Date', date('D, d M y H:i:s O', FACULA_TIME));

        $this->addLine('Message-ID', $this->getFactor() . '@' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost'));

        // Ready content for boundary check. Combine all content strings to one line, then check if the boundary existed.
        $checkContent = $mail['Subject'] . $mail['Body'] . $mail['AltBody'];

        while (true) {
            $this->mail['Boundary'] = '#' . $this->getFactor();
            $this->mail['BoundarySpliter'] = '--' . $this->mail['Boundary'];
            $this->mail['BoundarySpliterEnd'] = $this->mail['BoundarySpliter'] . '--';

            if (strpos($checkContent, $this->mail['Boundary']) === false) {
                break;
            }
        }

        $this->addLine('Content-Type', 'multipart/alternative; boundary="' . $this->mail['Boundary'] . '"');

        // Make mail body
        $this->addRaw(null); // Make space
        $this->addRaw('This MIME email produced by ' . $appInfo['Base'] . ' Mailer for ' . $senderHost . '.');
        $this->addRaw('If you have any problem reading this email, please contact ' . $mail['ReturnTo'] . ' for help.');
        $this->addRaw(null);

        // Primary content
        $this->addRaw($this->mail['BoundarySpliter']);
        $this->addLine('Content-Type', 'text/plain; charset=utf-8');
        $this->addLine('Content-Transfer-Encoding', 'base64');
        $this->addRaw(null);
        $this->addRaw($mail['AltBody']);
        $this->addRaw(null);

        $this->addRaw($this->mail['BoundarySpliter']);
        $this->addLine('Content-Type', 'text/html; charset=utf-8');
        $this->addLine('Content-Transfer-Encoding', 'base64');
        $this->addRaw(null);
        $this->addRaw($mail['Body']);
        $this->addRaw(null);

        $this->addRaw($this->mail['BoundarySpliterEnd']);

        return true;
    }

    public function get()
    {
        return implode("\r\n", $this->mailContent);
    }

    private function addLine($head, $content)
    {
        $this->mailContent[] = $head . ': ' . $content;

        return true;
    }

    private function addRaw($content)
    {
        $this->mailContent[] = $content;
    }

    private function getFactor()
    {
        return mt_rand(0, 65535) . mt_rand(0, 65535);
    }
}
