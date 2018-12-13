<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Foolain\Gt3\Model;

/**
 * Gt3 module configuration
 *
 * @api
 * @since 100.2.0
 */
interface ConfigInterface
{
    /**
     * Enabled config path
     */
    const XML_PATH_ENABLED = 'geetest/gt3/enabled';

    /**
     * Geetest gt3 id config path
     */
    const XML_PATH_GT3_ID = 'geetest/gt3/id';

    /**
     * Geetest gt3 key config path
     */
    const XML_PATH_GT3_KEY = 'geetest/gt3/key';

    /**
     * Check if geetest module is enabled
     *
     * @return bool
     * @since 100.2.0
     */
    public function isEnabled();

    /**
     * Return Geetest gt3 id
     *
     * @return string
     * @since 100.2.0
     */
    public function getId();

    /**
     * Return Geetest gt3 key
     *
     * @return string
     * @since 100.2.0
     */
    public function getKey();
}
