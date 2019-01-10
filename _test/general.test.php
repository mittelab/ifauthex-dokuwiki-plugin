<?php
/**
 * General tests for the ifauthex plugin
 *
 * @group plugin_ifauthex
 * @group plugins
 */
class general_plugin_ifauthex_test extends DokuWikiTest
{
    const VALID_EXPRESSIONS = array(
        'user',
        '!user',
        '@group',
        '!@group',
        '!(!@group && !@group && !@group)',
        '!(!@group && !user && @group) || !(@group || user || @group)',
        '!(!@group && @group || !user) && (!user || user && @group)',
        '!(!@group && user) && !(!@group || @group || user)',
        '!(!@group || !user && !user) || !(user || @group || !@group)',
        '!(!@group || @group || !@group) && (@group || @group && !@group)',
        '!(!@group || user || user) && !(!user || !user || !user)',
        '!(!user && !@group) || (!@group || !user && @group)',
        '!(!user && @group && @group) && (user || !@group || user)',
        '!(!user && user || !user) && !(@group || !@group && user)',
        '!(!user || !@group || user) || (!user || !@group && !@group)',
        '!(!user || @group && !user) || !(!@group && user || !user)',
        '!(!user || user || !@group) && !(user && user && !user)',
        '!(@group && !@group || !user) || (@group && @group || user)',
        '!(@group && !user) || !(!user && @group && user)',
        '!(@group && user && @group) && (!@group && !user)',
        '!(@group || !@group || !@group) || (user && !user || !user)',
        '!(@group || !user || user) || !(@group && !user && !user)',
        '!(@group || user && !user) && (!user && !@group || @group)',
        '!(user && !@group && user) && !(!@group && !@group && @group)',
        '!(user && !user || !user) || !(@group)',
        '!(user && @group) && (!user || user || !user)',
        '!(user || !@group && @group) && !(!@group || user && !user)',
        '!(user || !user || !@group) || !(user || @group || user)',
        '!(user || @group || user) && (@group || @group && user)',
        '!user || !user || user',
        '(!@group && !user && @group) || !(@group || !user || !user)',
        '(!@group && @group || !user) && (!user || !user && !user)',
        '(!@group && user) && !(!@group || !@group || @group)',
        '(!@group || !user && !user) || !(user || !@group && user)',
        '(!@group || @group || !@group) && (@group && user)',
        '(!@group || user || user) && !(!user && user || !@group)',
        '(!user && !@group) || (!@group && user && !user)',
        '(!user && @group && @group) && (user && @group || @group)',
        '(!user && user || !user) && !(@group && @group && @group)',
        '(!user || !@group || user) || (!user && !user)',
        '(!user || @group && !user) || !(!@group && !user || !@group)',
        '(!user || user || !@group) && !(user && !user && !@group)',
        '(@group && !@group || !user) || (@group && !@group || @group)',
        '(@group && !user) || !(!user && !@group && @group)',
        '(@group && user && @group) && !(user || user)',
        '(@group || !@group || !@group) || (@group || user || !user)',
        '(@group || !user || user) || !(!user || user && !user)',
        '(@group || user && !user) && (!@group || @group || @group)',
        '(user && !@group && @group) || (user || @group && user)',
        '(user && !user || !user) || !(@group || !user)',
        '(user && @group) && (!user || !user || !@group)',
        '(user || !@group && @group) && !(!@group || !user && !@group)',
        '(user || !user || !@group) || !(user || !@group || @group)',
        '(user || @group || user) && (@group || !@group && @group)',
        'user && user || @group'
    );

    /**
     * Simple test to make sure the plugin.info.txt is in correct format
     */
    public function test_plugininfo()
    {
        $file = __DIR__ . '/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('email', $info);
        $this->assertArrayHasKey('date', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);
        $this->assertArrayHasKey('url', $info);

        $this->assertEquals('ifauthex', $info['base']);
        $this->assertRegExp('/^https?:\/\//', $info['url']);
        $this->assertTrue(mail_isvalid($info['email']));
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));
    }

    /**
     * Test to ensure that every conf['...'] entry in conf/default.php has a corresponding meta['...'] entry in
     * conf/metadata.php.
     */
    public function test_plugin_conf()
    {
        $conf_file = __DIR__ . '/../conf/default.php';
        if (file_exists($conf_file)) {
            include($conf_file);
        }
        $meta_file = __DIR__ . '/../conf/metadata.php';
        if (file_exists($meta_file)) {
            include($meta_file);
        }

        $this->assertEquals(
            gettype($conf),
            gettype($meta),
            'Both ' . DOKU_PLUGIN . 'ifauthex/conf/default.php and ' . DOKU_PLUGIN . 'ifauthex/conf/metadata.php have to exist and contain the same keys.'
        );

        if (gettype($conf) != 'NULL' && gettype($meta) != 'NULL') {
            foreach ($conf as $key => $value) {
                $this->assertArrayHasKey(
                    $key,
                    $meta,
                    'Key $meta[\'' . $key . '\'] missing in ' . DOKU_PLUGIN . 'ifauthex/conf/metadata.php'
                );
            }

            foreach ($meta as $key => $value) {
                $this->assertArrayHasKey(
                    $key,
                    $conf,
                    'Key $conf[\'' . $key . '\'] missing in ' . DOKU_PLUGIN . 'ifauthex/conf/default.php'
                );
            }
        }

    }
}
