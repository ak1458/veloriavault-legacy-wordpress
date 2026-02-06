<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor;

use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Rule\Rule;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepositoryInterface;

defined('ABSPATH') or exit;

class ActivationTriggerStrategy
{
    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @param Rule $rule
     */
    public function __construct($rule)
    {
        $this->rule = $rule;
        $this->ruleRepository = new RuleRepository();
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function canBeAppliedUsingCouponCode($cart)
    {
        if ($this->rule->getActivationCouponCode() === null) {
            return true;
        }

        return in_array($this->rule->getActivationCouponCode(), $cart->getRuleTriggerCoupons(), true);
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function canBeAppliedByDate($cart)
    {
        static $rule_active = [];
        $context = $cart->getContext()->getGlobalContext();

        $key = md5( json_encode($context). $this->rule->getID() );
        if( isset($rule_active[$key]) )
            return $rule_active[$key];

        // it is not actually UTC.The time has already shifted by WP. UTC is for convenience.
        $date = (new \DateTime("now", new \DateTimeZone("UTC")))->setTimestamp($cart->getContext()->time());
        $date->setTime(0, 0, 0);

        if ($this->rule->getDateFrom()) {
            if ($this->rule->getDateFrom()->getTimestamp() > $date->getTimestamp()) {
                $rule_active[$key] = false;
                return false;
            }
        }

        if ($this->rule->getDateTo()) {
            if ($this->rule->getDateTo()->getTimestamp() <= $date->getTimestamp()) {

                if ($context->getOption("deactivate_rules_when_it_ends", false)) {
                    $this->ruleRepository->markAsDisabledByPlugin($this->rule->getId());
                }

                $rule_active[$key] = false;
                return false;
            }
        }

        $rule_active[$key] = true;
        return true;
    }
}
