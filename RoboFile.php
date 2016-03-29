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

  /**
   * Apply basic permissions to Drupal site.
   *
   * @see https://www.drupal.org/node/244924
   *
   * @param string $group
   *   User group to own site files.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function fixPerms($group = 'www-data')
  {
    $this->taskExecStack()
         ->dir("web")
         ->exec("chown -R $(whoami):{$group} .")
         ->exec("find . -type d -exec chmod u=rwx,g=rx,o= '{}' \;")
         ->exec("find . -type f -exec chmod u=rw,g=r,o= '{}' \;")
         ->run();

    $this->taskExecStack()
         ->dir("web/sites")
         ->exec("find . -type d -name files -exec chmod ug=rwx,o= '{}' \;")
         ->exec("for d in ./*/files; do    find \$d -type d -exec chmod ug=rwx,o= '{}' \;;    find \$d -type f -exec chmod ug=rw,o= '{}' \;; done")
         ->run();

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
