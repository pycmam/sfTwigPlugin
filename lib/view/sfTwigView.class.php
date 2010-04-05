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
 * A view that uses Twig as the templating engine.
 *
 * @package    sfTwigPlugin
 * @subpackage view
 * @author     Henrik Bjornskov <henrik@bearwoods.dk>
 */
class sfTwigView extends sfPHPView
{
    /**
     * @var Twig_Environment
     */
    protected $twig = null;

    /**
     * @var Twig_Loader_Filesystem
     */
    protected $loader = null;

    /**
     * @var sfApplicationConfiguration
     */
    protected $configuration = null;

    /**
     * @var string Extension used by twig templates. which is .html
     */
    protected $extension = '.html';


    /**
     * Loads the Twig instance and registers the autoloader.
     */
    public function configure()
    {
        parent::configure();

        $this->configuration = $this->context->getConfiguration();

        // template name
        $template = sfConfig::get('sf_template');
        $templateDir = sfConfig::get('sf_template_dir') .'/'. $template;

        if (! file_exists($templateDir)) {
            throw new sfException(__METHOD__.": Couldn't find template " . $template);
        }

        // decorator template path
        $layoutTemplateDir = $templateDir .'/global';
        if (is_readable($layoutTemplateDir .'/'. $this->getDecoratorTemplate())) {
            $this->setDecoratorDirectory($layoutTemplateDir);
        }

        // module template path
        $moduleTemplateDir = $templateDir .'/modules/'. $this->moduleName;
        if (is_readable($moduleTemplateDir .'/'. $this->getTemplate())) {
            $this->setDirectory($moduleTemplateDir);
        }

        // init twig engine
        // empty array becuase it changes based on the rendering context
        $this->loader = new Twig_Loader_Filesystem(array());

        $this->twig = new sfTwigEnvironment($this->loader, array(
            'cache'      => sfConfig::get('sf_template_cache_dir') .'/'. $template,
            'debug'      => sfConfig::get('sf_debug', false),
            'sf_context' => $this->context,
        ));

        if ($this->twig->isDebug())
        {
            $this->twig->setAutoReload(true);
        }

        $this->loadExtensions();
    }

    /**
     * Returns the Twig_Environment
     *
     * @return Twig_Environment
     */
    public function getEngine()
    {
        return $this->twig;
    }

    /**
     * Loads standard extensions for Symfony into the view.
     */
    protected function loadExtensions()
    {
        // should be replaced with sf_twig_standard_extensions
        $prefixes = array_merge(array('Helper', 'Url', 'Asset', 'Tag', 'Escaping', 'Partial', 'I18N'), sfConfig::get('sf_standard_helpers'));

        foreach ($prefixes as $prefix) {
            $class_name = $prefix.'_Twig_Extension';
            if (class_exists($class_name)) {
                $this->twig->addExtension(new $class_name());
            }
        }

        // for now the extensions needs the original helpers so lets load thoose.
        $this->configuration->loadHelpers($prefixes);

        // makes it possible to load custom twig extensions.
        foreach (sfConfig::get('sf_twig_extensions', array()) as $extension) {
            if (!class_exists($extension)) {
                throw new InvalidArgumentException(sprintf('Unable to load "%s" as an Twig_Extension into Twig_Environment', $extension));
            }
            $this->twig->addExtension(new $extension());
        }
    }

    /**
     * This renders a file based on the $file and sf_type.
     *
     * @param string $file the fullpath to the template file
     *
     * @return string
     */
    protected function renderFile($file)
    {
        if (sfConfig::get('sf_logging_enabled', false)) {
            $this->dispatcher->notify(new sfEvent($this, 'application.log', array(sprintf('Render "%s"', $file))));
        }

        $this->loader->setPaths((array) realpath(dirname($file)));

        $event = $this->dispatcher->filter(new sfEvent($this, 'template.filter_parameters'), $this->attributeHolder->getAll());

        return $this->twig->loadTemplate(basename($file))->render($event->getReturnValue());
    }
}
