<?php

/*
 * This file is part of the sfTwigPlugin package.
 *
 * (c) Henrik Bjornskov <henrik@bearwoods.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A partial view that uses Twig as the templating engine.
 *
 * @package    sfTwigPlugin
 * @subpackage view
 * @author     Henrik Bjornskov <henrik@bearwoods.dk>
 */
class sfTwigPartialView extends sfTwigView
{
    /**
     * @var array of variables to pass to the partial template
     */
    protected $partialVars = array();

    /**
     * Method used by symfony to force add the extra variables when rendering a partial
     *
     * @param array $variables
     */
    public function setPartialVars(array $variables)
    {
        $this->partialVars = $variables;
        $this->getAttributeHolder()->add($variables);
    }

    /**
     * Invokes the parent configure and forces the this view object not to decorate.
     */
    public function configure()
    {
        parent::configure();

        // template name
        $template = sfConfig::get('sf_template');
        $templateDir = sfConfig::get('sf_template_dir') .'/'. $template;

        $this->setDecorator(false);
        $this->setDirectory($templateDir .'/'. $this->moduleName .'/'. $this->getTemplate());

        if ('global' == $this->moduleName)
        {
            $this->setDirectory($templateDir .'/global/'. $this->getTemplate());
        }
    }

    /**
     * Overwrite until caching have been implemented fully into this class.
     */
    public function getCache()
    {
    }
}
