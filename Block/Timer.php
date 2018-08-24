<?php

/**
 * @package     BlueAcorn\ProductCountdown
 * @version     1.0.0
 * @author      Blue Acorn, LLC. <code@blueacorn.com>
 * @copyright   Copyright © 2018 Blue Acorn, LLC.
 */

namespace BlueAcorn\ProductCountdown\Block;

class Timer extends \Magento\Catalog\Block\Product\View\Description {

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context
    , \Magento\Framework\Registry $registry
    , \Magento\Framework\Stdlib\DateTime\DateTime $date
    , \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    , \Magento\Catalog\Model\ProductFactory $productfac
    , array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->date = $date;
        $this->_timezoneInterface = $timezone;
        $this->ProductFac = $productfac;
        // $today = $this->_timezoneInterface->date()->format('m/d/y H:i:s');
    }

    public function getProductTimer($pId = null) {

        if ($pId) {
            $p = $this->ProductFac->create()->load($pId);

            $old_date = $p->getCountdownTimer();
            $old_date_timestamp = strtotime($old_date);
            return date('D M j G:i:s T Y', $old_date_timestamp) ?? false;
        } else {
            $old_date = $this->getProduct()->getCountdownTimer();
            $old_date_timestamp = strtotime($old_date);
            return date('D M j G:i:s T Y', $old_date_timestamp);
        }
    }

    public function showTimer($date = null) {
        $date = $date ?? $this->getProductTimer();
        return strtotime($date) >= strtotime(date('m/d/y H:i:s')) ? true : false;
    }

    public function getTimerHtml($product) {

        $time = $this->getProductTimer($product->getId());
        if ($this->showTimer($time) && $time) {
            $html = '<span>ends in</span>
                <span class="timer-container" data-countdown="' . $time . '" data-mage-init=\'{"ba.timer":{}}\'></span>';
            return $html;
        }
    }

}
