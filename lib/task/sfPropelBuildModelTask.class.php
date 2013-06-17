<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once(dirname(__FILE__).'/../propel-override/builder/om/PHP5ExtensionPeerBuilder.php');
require_once(dirname(__FILE__).'/../propel-override/builder/om/ExtensionQueryBuilder.php');

require_once(dirname(__FILE__).'/sfPropelBaseTask.class.php');

/**
 * Create classes for the current model.
 *
 * @package    symfony
 * @subpackage propel
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfPropelBuildModelTask.class.php 23922 2009-11-14 14:58:38Z fabien $
 */
class sfPropelBuildModelTask extends sfPropelBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      new sfCommandOption('phing-arg', null, sfCommandOption::PARAMETER_REQUIRED | sfCommandOption::IS_ARRAY, 'Arbitrary phing argument'),
    ));

    $this->namespace = 'propel';
    $this->name = 'build-model';
    $this->briefDescription = 'Creates classes for the current model';

    $this->detailedDescription = <<<EOF
The [propel:build-model|INFO] task creates model classes from the schema:

  [./symfony propel:build-model|INFO]

The task read the schema information in [config/*schema.xml|COMMENT] and/or
[config/*schema.yml|COMMENT] from the project and all installed plugins.

You mix and match YML and XML schema files. The task will convert
YML ones to XML before calling the Propel task.

The model classes files are created in [lib/model|COMMENT].

This task never overrides custom classes in [lib/model|COMMENT].
It only replaces files in [lib/model/om|COMMENT] and [lib/model/map|COMMENT].
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->schemaToXML(self::DO_NOT_CHECK_SCHEMA, 'generated-');
    $this->copyXmlSchemaFromPlugins('generated-');

    $databaseManager = new sfDatabaseManager($this->configuration);

    // create a buildtime-conf.xml file
    $buildTimeFile = sfConfig::get('sf_config_dir').'/buildtime-conf.xml';
    $this->createBuildTimeFile($databaseManager, $buildTimeFile);

    $ret = $this->callPhing('om', self::CHECK_SCHEMA);
    $this->cleanup();

    if (is_file($buildTimeFile))
    {
      unlink($buildTimeFile);
    }

    $this->reloadAutoload();

    return !$ret;
  }
}
