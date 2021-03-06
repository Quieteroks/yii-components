<?php

namespace quieteroks\components\web;

use ArrayObject;
use yii\base\Action;
use yii\base\InlineAction;
use yii\base\InvalidConfigException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use quieteroks\components\base\ActionRule;
use quieteroks\components\di\MethodArguments;

abstract class Controller extends \yii\web\Controller
{
    /**
     * @var ArrayObject
     */
    private $_rules;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return $this->prepareAccessBehaviors();
    }

    /**
     * Returns the validation rules for AccessControl and VerbFilter.
     *
     * Example:
     *
     * ```php
     * // actions, methods, roles, allow, accessExtraParams
     * return [
     *      ['index, view', 'get', '', true, [ ... ]],
     *      [['create', 'update'], ['get', 'post']],
     *      ['delete', 'post, delete'],
     * ];
     * ```
     *
     * @return array|ActionRule[]
     */
    abstract protected function rules() : array;

    /**
     * Binds the parameters to the action.
     *
     * @param Action $action
     * @param array $params
     * @return array
     * @throws BadRequestHttpException
     */
    public function bindActionParams($action, $params) : array
    {
        try {

            if ($action instanceof InlineAction) {
                $method = [$this, $action->actionMethod];
            } else {
                $method = [$action, 'run'];
            }
            $method = new MethodArguments($method, $params);
            $args = $method->getArguments();

        } catch (InvalidConfigException $e) {
            throw new BadRequestHttpException(
                $e->getMessage(), $e->getCode(), $e
            );
        }
        $this->actionParams = $args;
        return array_values($args);
    }

    /**
     * Returns a compile actions access and verb rules.
     *
     * @return ArrayObject|ActionRule[]
     */
    public function getActionsRules() : ArrayObject
    {
        if (is_null($this->_rules)) {
            $this->_rules = $this->createActionsAccessRules();
        }
        return $this->_rules;
    }

    /**
     * Create rules objects.
     *
     * @return ArrayObject|ActionRule[]
     */
    protected function createActionsAccessRules() : ArrayObject
    {
        $rules = new ArrayObject();
        foreach ($this->rules() as $rule) {
            if (!($rule instanceof ActionRule)) {
                $rule = new ActionRule(...$rule);
            }
            $rules->append($rule);
        }
        return $rules;
    }

    /**
     * Prepare ActionControl and VerbFilter behaviors.
     *
     * @return array
     */
    protected function prepareAccessBehaviors() : array
    {
        $verbs = [];
        $access = [];
        foreach ($this->getActionsRules() as $rule) {
            if ($filter = $rule->getVerbFilter()) {
                $verbs = array_merge($verbs, $filter);
            }
            if ($filter = $rule->getAccessFilter()) {
                $access[] = $filter;
            }
        }
        return [
            'verb' => $this->verbFilterBehavior($verbs),
            'access' => $this->accessControlBehavior($access),
        ];
    }

    /**
     * Action filter that filters by HTTP request methods.
     *
     * @param array $actions
     * @return array
     */
    protected function verbFilterBehavior(array $actions) : array
    {
        return [
            'class' => VerbFilter::class,
            'actions' => $actions,
        ];
    }

    /**
     * Simple access control based on a set of rules.
     *
     * @param array $rules
     * @return array
     */
    protected function accessControlBehavior(array $rules) : array
    {
        $only = [];
        foreach ($rules as $rule) {
            $only = array_merge($only, $rule['actions']);
        }
        return [
            'class' => AccessControl::class,
            'only' => array_values(array_unique($only)),
            'rules' => $rules,
        ];
    }
}
