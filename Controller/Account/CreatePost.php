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
use Magento\Framework\App\ObjectManager;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\Address;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Customer\Model\CustomerExtractor;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Data\Form\FormKey\Validator;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePost extends \Magento\Customer\Controller\Account\CreatePost
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
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        Address $addressHelper,
        UrlFactory $urlFactory,
        FormFactory $formFactory,
        SubscriberFactory $subscriberFactory,
        RegionInterfaceFactory $regionDataFactory,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerUrl $customerUrl,
        Registration $registration,
        Escaper $escaper,
        CustomerExtractor $customerExtractor,
        DataObjectHelper $dataObjectHelper,
        AccountRedirect $accountRedirect,
        Validator $formKeyValidator,
        ConfigInterface $gt3Config,
        SessionManagerInterface $coreSession
    ) {
        $this->coreSession = $coreSession;
        $this->gt3Config = $gt3Config;
        parent::__construct(
            $context,
            $customerSession,
            $scopeConfig,
            $storeManager,
            $accountManagement,
            $addressHelper,
            $urlFactory,
            $formFactory,
            $subscriberFactory,
            $regionDataFactory,
            $addressDataFactory,
            $customerDataFactory,
            $customerUrl,
            $registration,
            $escaper,
            $customerExtractor,
            $dataObjectHelper,
            $accountRedirect,
            $formKeyValidator
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
                $this->messageManager->addError($this->escaper->escapeHtml($e->getMessage()));
                foreach ($e->getErrors() as $error) {
                    $this->messageManager->addError($this->escaper->escapeHtml($error->getMessage()));
                }
                $url = $this->urlModel->getUrl('*/*/create', ['_secure' => true]);
                return $this->resultRedirectFactory->create()->setUrl($this->_redirect->error($url));
            }
        }//End Of Gt3 Server slide validation

        return parent::execute();
    }
}
