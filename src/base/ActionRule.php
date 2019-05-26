<?php

namespace quieteroks\components\base;

class ActionRule
{
    /**
     * @var array list of action IDs that this rule applies to.
     *
     * @see \yii\filters\AccessRule::$actions
     * @see \yii\filters\VerbFilter::$actions
     */
    protected $actions;
    /**
     * @var array list of request methods (e.g. `GET`, `POST`) that this rule applies to.
     *
     * @see \yii\filters\AccessRule::$verbs
     * @see \yii\filters\VerbFilter::$actions
     */
    protected $methods;
    /**
     * @var bool whether this is an 'allow' rule or 'deny' rule.
     *
     * @see \yii\filters\AccessRule::$allow
     */
    protected $allow;
    /**
     * @var array list of roles that this rule applies to.
     *
     * @see \yii\filters\AccessRule::$roles
     */
    protected $roles;
    /**
     * @var array parameters to pass to the constructing AccessRule filter.
     *
     * @see \yii\filters\AccessRule
     */
    protected $accessExtraParams = [];

    /**
     * ActionRule constructor.
     *
     * @param string|array $actions
     * @param string|array $methods
     * @param string $roles
     * @param bool $allow
     * @param array $accessExtraParams
     */
    public function __construct(
        $actions,
        $methods,
        $roles = '',
        $allow = true,
        array $accessExtraParams = []
    ) {
        $this->actions = $this->prepareParams($actions);
        $this->methods = $this->prepareParams($methods);
        $this->roles = $this->prepareParams($roles);
        $this->allow = (boolean)$allow;
        $this->accessExtraParams = $this->prepareParams($accessExtraParams);
    }

    /**
     * VerbFilter configuration
     *
     * @return array
     */
    public function getVerbFilter() : array
    {
        $result = [];
        foreach ($this->actions as $action) {
            $result[$action] = $this->methods;
        }
        return $result;
    }

    /**
     * AccessControl configuration
     *
     * @return array
     */
    public function getAccessFilter() : array
    {
        return array_merge(
            $this->accessExtraParams,
            [
                'actions' => $this->actions,
                'allow' => $this->allow,
                'roles' => $this->roles,
                'verbs' => $this->methods,
            ]
        );
    }

    /**
     * Prepare different param types to array.
     *
     * @param string|array $params
     * @return array
     */
    protected function prepareParams($params) : array
    {
        if (!is_array($params)) {
            $params = explode(',', $params);
        }
        return array_filter(
            array_map('trim', $params)
        );
    }
}
