<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Foolain\Gt3\Controller\Account;

/**
 * {@inheritdoc}
 */
use Foolain\Gt3\Lib\GeetestLib;
use Foolain\Gt3\Model\ConfigInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\App\ObjectManager;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Controller\AbstractAccount;
use Magento\Framework\Phrase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoginPost extends \Magento\Customer\Controller\Account\LoginPost
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
     * {@inheritdoc}
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        Validator $formKeyValidator,
        AccountRedirect $accountRedirect,
        ConfigInterface $gt3Config,
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
        $this->gt3Config = $gt3Config;
        parent::__construct(
            $context,
            $customerSession,
            $customerAccountManagement,
            $customerHelperData,
            $formKeyValidator,
            $accountRedirect
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        //Gt3 Server slide validation
        if ($this->gt3Config->isEnabled()) {
            try {
                $GtSdk = new GeetestLib($this->gt3Config->getId(), $this->gt3Config->getKey());

                $remoteAddress = ObjectManager::getInstance()->get(
                                    \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);
                $ipAddress = $remoteAddress->getRemoteAddress();

                $data = [
                        "user_id" => $this->coreSession->getUser_id(), # 网站用户id
                        "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                        "ip_address" => $ipAddress # 请在此处传输用户请求验证时所携带的IP
                    ];

                $username = $this->getRequest()->getParam('username');
                $geetest_challenge = $this->getRequest()->getParam('geetest_challenge');
                $geetest_validate  = $this->getRequest()->getParam('geetest_validate');
                $geetest_seccode   = $this->getRequest()->getParam('geetest_seccode');

                if ($this->coreSession->getGtserver() == 1) {   //服务器正常
                    $result = $GtSdk->success_validate($geetest_challenge, $geetest_validate, $geetest_seccode, $data);
                    if ($result) {
                    } else {
                        throw new InputException(__('Captcha is invalid!'));
                    }
                } else {  //服务器宕机,走failback模式
                    if ($GtSdk->fail_validate($geetest_challenge, $geetest_validate, $geetest_seccode)) {
                    } else {
                        throw new InputException(__('Captcha is invalid!'));
                    }
                }
            } catch (InputException $e) {
                $this->session->setUsername($username);
                $this->messageManager->addError($e->getMessage());
                foreach ($e->getErrors() as $error) {
                    $this->messageManager->addError($error->getMessage());
                }
                return $this->accountRedirect->getRedirect();
            }
        }//End Of Gt3 Server slide validation

        return parent::execute();
    }
}
