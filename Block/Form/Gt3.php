<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Foolain\Gt3\Block\Form;

/**
 * Customer register form block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Gt3 extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Foolain\Gt3\Model\ConfigInterface
     */
    private $_geetest;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Foolain\Gt3\Model\ConfigInterface $geetest,
        array $data = []
    ) {
        $this->_geetest = $geetest;
        parent::__construct($context, $data);
    }

    /**
     * Get gt3 status
     */
    public function gt3IsEnabled()
    {
        return $this->_geetest->isEnabled();
    }

    /**
     * Get gt3 ID
     */
    public function getGt3Id()
    {
        return $this->_geetest->getId();
    }

    /**
     * Get gt3 KEY
     */
    public function getGt3Key()
    {
        return $this->_geetest->getKey();
    }
}
