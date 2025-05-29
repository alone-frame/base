<?php

namespace AloneFrame\base\plugin;

use AloneFrame\base\Frame;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * https://github.com/PHPMailer/PHPMailer/
 * composer require phpmailer/phpmailer
 */
class PhpMail{

    /**
     * @param $arr
     * @return bool
     * @throws Exception
     */
    public static function send($arr): bool {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        if (Frame::strIn(Frame::getArr($arr, 'smtp'), 'office365.com')) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        } else {
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;
        }
        $mail->CharSet = "UTF-8";
        $mail->isHTML(true);
        $mail->Host = Frame::getArr($arr, 'smtp');
        $mail->Username = Frame::getArr($arr, 'user');
        $mail->Password = Frame::getArr($arr, 'pass');
        $mail->setFrom(Frame::getArr($arr, 'user', Frame::getArr($arr, 'name')));
        if (!empty($res = Frame::getArr($arr, 'addressee'))) {
            $resArr = explode(',', $res);
            foreach ($resArr as $v) {
                $mail->addAddress($v);
            }
        }
        if (!empty($cc = Frame::getArr($arr, 'cc'))) {
            $ccArr = explode(',', $cc);
            foreach ($ccArr as $v) {
                $mail->addCC($v);
            }
        }
        if (!empty($bcc = Frame::getArr($arr, 'bcc'))) {
            $bccArr = explode(',', $bcc);
            foreach ($bccArr as $v) {
                $mail->addBCC($v);
            }
        }
        $mail->Subject = Frame::getArr($arr, 'title');
        $mail->Body = Frame::getArr($arr, 'content');
        $mail->SMTPDebug = 0;
        return $mail->send();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public static function demo(): bool {

        //实例化并传递“true”将启用异常
        $Mail = new PHPMailer();

        //使用SMTP发送
        $Mail->isSMTP();

        //设定邮件编码
        $Mail->CharSet = "UTF-8";

        //SMTP服务器
        $Mail->Host = 'smtp.qq.com';

        //允许 SMTP 认证
        $Mail->SMTPAuth = true;

        //SMTP 用户名  即邮箱的用户名
        $Mail->Username = 'xxxxxxxx@qq.com';

        //SMTP 密码  部分邮箱是授权码
        $Mail->Password = 'hzxtzmkjgprnbijf';

        //允许 TLS 或者ssl协议
        $Mail->SMTPSecure = 'ssl';

        //服务器端口 25 或者465 具体要看邮箱服务器支持
        $Mail->Port = 465;

        //发件人(第二个参数:名称可选)
        $Mail->setFrom('xxxxxxxx@qq.com');

        //收件人可多次调用(第二个参数:名称可选)
        $Mail->addAddress('xxxx@qq.com');

        //邮件标题
        $Mail->Subject = '邮件标题';

        //是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
        $Mail->isHTML(true);

        //HTML邮件内容
        $Mail->Body = 'HTML邮件内容';

        //这是非HTML邮件客户端的纯文本正文
        $Mail->AltBody = "这是非HTML邮件客户端的纯文本正文";

        //回复的时候回复给哪个邮箱 建议和发件人一致(第二个参数:名称可选)
        $Mail->addReplyTo('xxxxxxxx@example.com');

        //抄送(第二个参数:名称可选)
        $Mail->addCC('xxx@example.com');

        //密送(第二个参数:名称可选)
        $Mail->addBCC('xx@example.com');

        //发送附件可多次调用(第二个参数:重命名可选)
        $Mail->addAttachment('../xy.zip');

        //调试模式输出 (0/1)
        $Mail->SMTPDebug = 0;

        return $Mail->send();
    }
}