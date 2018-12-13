<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Foolain\Gt3\Controller\gt3;

use Foolain\Gt3\Lib\GeetestLib;
use Foolain\Gt3\Model\ConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Session\SessionManagerInterface;

class InitCaptcha extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ConfigInterface
     */
    private $gt3Config;

    /**
     * @var SessionManagerInterface
     */
    private $coreSession;

    /**
     * @param Context $context
     * @param ConfigInterface $gt3Config
     */
    public function __construct(
        Context $context,
        ConfigInterface $gt3Config,
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession    = $coreSession;
        $this->gt3Config  = $gt3Config;
        parent::__construct($context);
    }

    /**
     * InitCaptcha action
     *
     * @return $this
     */
    public function execute()
    {
        if (!$this->gt3Config->isEnabled()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $this->coreSession->start();
        $sessionId = $this->coreSession->getSessionId();

        $remoteAddress = $this->_objectManager->create('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
        $ipAddress = $remoteAddress->getRemoteAddress();

        $GtSdk = new GeetestLib($this->gt3Config->getId(), $this->gt3Config->getKey());

        $data = [
                "user_id" => $sessionId, # 网站用户id
                "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                "ip_address" => $ipAddress # 请在此处传输用户请求验证时所携带的IP
            ];
        $status = $GtSdk->pre_process($data, 1);
        $this->coreSession->setGtserver($status);
        $this->coreSession->setUser_id($data['user_id']);
        return $this->getResponse()->setBody($GtSdk->get_response_str());
    }
}
