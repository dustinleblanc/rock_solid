<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
  use \Boedah\Robo\Task\Drush\loadTasks;

  /**
   * The location of our Drush executable.
   */
  const DRUSH_BIN = "bin/drush";

  /**
   * The location of our Codeception executable.
   */
  const CODECEPT_BIN = "bin/codecept";

  /**
   * RoboFile constructor.
   */
  public function __construct()
  {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }

  /**
   * Build the site into the document root, ensuring our custom code and settings
   * are able to be loaded by Drupal in either our local environment or on
   * Pantheon in production.
   *
   * @param string $env
   *
   * @return $this
   */
  public function build()
  {
    $this->taskMirrorDir([
        'src/modules' => 'web/modules/custom',
        'src/themes' => 'web/themes/custom',
    ])->run();
    $this->_copy('src/settings.php', 'web/sites/default/settings.php');
    if (getenv('PROD_DEST') == 'pantheon') {
      $this->_copy('src/settings.pantheon.php', 'web/sites/default/settings.pantheon.php');
    }
    $this->_touch(".built");
    return $this;
  }

  /**
   * Deploy site to Pantheon.
   */
  public function deploy()
  {
    // Get a copy of Pantheon's repository and clean it out.
    $this->taskGitStack()
         ->dir('../')
         ->cloneRepo(getenv('PANTHEON_REPO'), 'pantheon')
         ->run();
    $this->_cleanDir('../pantheon');
    // Put all our code in the repo.
    $this->_mirrorDir((__DIR__), '../pantheon');
    // Get rid of dev dependencies
    $this->taskComposerInstall()
         ->dir('../pantheon')
         ->preferDist()
         ->noDev()
         ->option('no-scripts')
         ->run();
    // Force commit and push the code.
    $sha = getenv('CIRCLE_SHA1');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $this->taskGitStack()
         ->dir('../pantheon')
         ->add('-Af')
         ->commit("Successful verified merge of {$username} {$sha}.")
         ->exec('push origin master -f')
         ->run();
  }

  /**
   * Install Drupal using some assumed defaults.
   */
  public function install($env = "dev")
  {
    if (!file_exists(".built")) {
      $this->build();
    }
    $this->buildDrushTask()
        ->siteName(getenv('SITE_NAME'))
        ->siteMail(getenv('SITE_MAIL'))
        ->locale(getenv('LOCALE'))
        ->accountMail(getenv('ADMIN_MAIL'))
        ->accountName(getenv('ADMIN_NAME'))
        ->siteInstall(getenv('SITE_PROFILE'))
        ->run();
  }

  /**
   * Runs Codeception Test Suite.
   */
  public function test()
  {
    $this->taskCodecept(self::CODECEPT_BIN)->run();
  }

  public function fixPerms()
  {
    
  }
  /**
   * Set all of our defaults for Drush tasks so we don't have to repeat boilerplate.
   *
   * @return $this
   */
  private function buildDrushTask()
  {
    return $this->taskDrushStack($this::DRUSH_BIN)
                ->drupalRootDirectory((__DIR__) . '/web');
  }
}
