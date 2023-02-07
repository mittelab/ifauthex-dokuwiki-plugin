<?php
require_once(__DIR__ . '/../lib/grammar.php');
require_once(__DIR__ . '/../lib/exceptions.php');

/**
 * General tests for the ifauthex plugin
 *
 * @group plugin_ifauthex
 * @group plugins
 */
class general_plugin_ifauthex_test extends DokuWikiTest
{

    protected $pluginsEnabled = array('ifauthex');

    const VALID_EXPRESSIONS = array(
        'user',
        '"user name"',
        'user-name',
        'user.name',
        '"user \\ name \\" surname"',
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

    const VALID_MB_EXPRESSIONS = array(
        "\u{7D4C}\u{55B6}\u{4F01}\u{753B}\u{672C}\u{90E8}",
        "\u{7D4C}\u{55B6}\u{4F01} && \u{753B}\u{672C}\u{90E8}",
        "\u{7D4C}\u{55B6}\u{4F01} && \u{753B} || @\u{672C}\u{90E8}",
        "!@\u{7D4C} || (\u{55B6}\u{4F01} && \u{753B} || @\u{672C}) && !\u{90E8}"
    );

    const UNKNOWN_TOKEN_EXPRESSIONS = array(
        '!(!@group & !@group && !@group)',
        '!(!@group && !user && @group) | !(@group || user || @group)',
        '!(!@group && @group || !user] && (!user || user && @group)',
        '!(!@group && user) && !^!@group || @group || user)',
        '!(!@group || <inject> !user && !user) || !(user || @group || !@group)',
        '!(!@group || @group {--} !@group) && (@group || @group && !@group)',
        '!(!@group || user || user) && !(!user || > !user || !user)',
        '!(!user && !@group) <|| (!@group || !user && @group)',
        '!(!user && @group && @group) / && (user || !@group || user)',
        '!(!user && user || : !user) && !(@group || !@group && user)',
        '"user name\\"',
        '"user name\\\\\\"'
    );

    const STRAY_TOKEN_EXPRESSIONS = array(
        'usr usr2',
        'user && @group) && (!user || !user || !@group)',
        // Closed brackets alone are unmatched:
        '@group && (usr) @another',
        '(user || !user || !@group) || !user || !@group || @group)',
    );

    const UNMATCHED_WRAPPER_EXPRESSIONS = array(
        '(user || !@group && @group && !(!@group || !user && !@group)',
        '(user || @group || user) && (@group || !@group && @group'
    );

    const NOT_ENOUGH_ARGS_EXPRESSIONS = array(
        '!(@group && user && @group) && (!@group && !)',
        '!(@group || !@group || !@group) || (user &&)',
        '!(@group || !user ||) || !(@)',
        '!(@group || user && !user) && @',
        '!(@group || user && !user) ||',
        '@!usr', // @ takes "!", but when ! is parsed, no arg is left
        '@@group', // @ takes "@", but when ! is parsed, no arg is left
    );

    const MALFORMED_EXPRESSIONS = array(
        'usr usr2', // More than one element in root
        '()', // Subexpression must have one root
        '(usr usr2)',
        '@()', // Group takes exactly a literal
        '@(group)'
    );

    public static function strip($txt) {
        return preg_replace('/\s/', '', $txt);
    }

    public function test_parse()
    {
        foreach (self::VALID_EXPRESSIONS as $expr) {
            $failureMsg = 'Assertion failed at expression "' . $expr . '".';
            $ast = null;
            $rebuiltExpr = null;
            $this->assertNotNull($ast = auth_expr_parse($expr));
            $this->assertNotNull($rebuiltExpr = $ast->getRepresentation());
            $this->assertEquals(self::strip($rebuiltExpr), self::strip($expr));
        }
        if (\AST\TokenDefinition::supportsMultibyte()) {
            foreach (self::VALID_MB_EXPRESSIONS as $expr) {
                $failureMsg = 'Assertion failed at expression "' . $expr . '".';
                $ast = null;
                $rebuiltExpr = null;
                $this->assertNotNull($ast = auth_expr_parse($expr));
                $this->assertNotNull($rebuiltExpr = $ast->getRepresentation());
                $this->assertEquals(self::strip($rebuiltExpr), self::strip($expr));
            }
        }
    }

    public function test_unknown_token()
    {
        foreach (self::UNKNOWN_TOKEN_EXPRESSIONS as $expr) {
            $exc = null;
            try {
                auth_expr_parse($expr);
            } catch (Exception $e) {
                $exc = $e;
            }
            $this->assertInstanceOf(\AST\UnknownTokenException::class, $exc);
        }
    }

    public function test_unmatched_wrappers()
    {
        foreach (self::UNMATCHED_WRAPPER_EXPRESSIONS as $expr) {
            $exc = null;
            try {
                auth_expr_parse($expr);
            } catch (Exception $e) {
                $exc = $e;
            }
            $this->assertInstanceOf(\AST\UnmatchedWrapperException::class, $exc);
        }
    }


    public function test_not_enough_args()
    {
        foreach (self::NOT_ENOUGH_ARGS_EXPRESSIONS as $expr) {
            $exc = null;
            try {
                auth_expr_parse($expr);
            } catch (Exception $e) {
                $exc = $e;
            }
            $this->assertInstanceOf(\AST\NotEnoughArgumentsException::class, $exc);
        }
    }

    public function test_malformed()
    {
        foreach (self::MALFORMED_EXPRESSIONS as $expr) {
            $exc = null;
            try {
                $ast = null;
                // The expression must parse, but not validate
                $this->assertNotNull($ast = auth_expr_parse($expr));
                $ast->ensureWellFormed();
            } catch (Exception $e) {
                $exc = $e;
            }
            $this->assertInstanceOf(\AST\MalformedExpressionException::class, $exc);
        }
    }

    public function test_empty_parentehses() {
        // This must not throw. It's malformed, but it's parsed correctly.
        $this->assertNotNull(auth_expr_parse('()'));
    }

    public function test_empty() {
        // This must not throw. It's malformed, but it's parsed correctly.
        $this->assertNotNull(auth_expr_parse(''));
    }


    public function test_depth_limit()
    {
        $depthLimit = \AST\parse_config()->EXPR_DEPTH_LIMIT;
        $this->expectException(RuntimeException::class);
        auth_expr_parse(str_repeat('(', $depthLimit) . 'a && b' . str_repeat(')', $depthLimit));
    }

    public function test_output() {
        $info = array();
        $instructions = p_get_instructions('<ifauth (nonexistent)>hideme</ifauth><ifauth !(nonexistent)>showme</ifauth>');
        $xhtml = p_render('xhtml', $instructions, $info);
        $this->assertTrue(stristr($xhtml, 'showme') !== false);
        $this->assertTrue(stristr($xhtml, 'hideme') === false);

        auth_expr_evaluation_context()->SIMULATE_USERS = array('nonexistent');
        $xhtml = p_render('xhtml', $instructions, $info);
        $this->assertTrue(stristr($xhtml, 'showme') === false);
        $this->assertTrue(stristr($xhtml, 'hideme') !== false);
        auth_expr_evaluation_context()->SIMULATE_USERS = null;
    }

    public function test_operators() {
        auth_expr_evaluation_context()->SIMULATE_IN_GROUPS = array('group');
        auth_expr_evaluation_context()->SIMULATE_USERS = array('user');

        $this->assertTrue(auth_expr_parse('user')->evaluate());
        $this->assertFalse(auth_expr_parse('!user')->evaluate());
        $this->assertTrue(auth_expr_parse('!user2')->evaluate());
        $this->assertFalse(auth_expr_parse('user2')->evaluate());
        $this->assertTrue(auth_expr_parse('@group')->evaluate());
        $this->assertFalse(auth_expr_parse('!@group')->evaluate());
        $this->assertTrue(auth_expr_parse('!@group2')->evaluate());
        $this->assertFalse(auth_expr_parse('@group2')->evaluate());

        $this->assertTrue(auth_expr_parse('user || @group')->evaluate());
        $this->assertTrue(auth_expr_parse('user || !@group')->evaluate());
        $this->assertTrue(auth_expr_parse('!user || @group')->evaluate());
        $this->assertFalse(auth_expr_parse('!user || !@group')->evaluate());
        $this->assertFalse(auth_expr_parse('user2 || @group2')->evaluate());
        $this->assertTrue(auth_expr_parse('user || @group2')->evaluate());
        $this->assertTrue(auth_expr_parse('user2 || @group')->evaluate());

        $this->assertTrue(auth_expr_parse('user && @group')->evaluate());
        $this->assertFalse(auth_expr_parse('user && !@group')->evaluate());
        $this->assertFalse(auth_expr_parse('!user && @group')->evaluate());
        $this->assertFalse(auth_expr_parse('!user && !@group')->evaluate());
        $this->assertFalse(auth_expr_parse('user2 && @group2')->evaluate());
        $this->assertFalse(auth_expr_parse('user && @group2')->evaluate());
        $this->assertFalse(auth_expr_parse('user2 && @group')->evaluate());

        $this->assertTrue(auth_expr_parse('(user || @group)')->evaluate());
        $this->assertTrue(auth_expr_parse('(user || !@group)')->evaluate());
        $this->assertTrue(auth_expr_parse('(!user || @group)')->evaluate());
        $this->assertFalse(auth_expr_parse('(!user || !@group)')->evaluate());
        $this->assertFalse(auth_expr_parse('(user2 || @group2)')->evaluate());
        $this->assertTrue(auth_expr_parse('(user || @group2)')->evaluate());
        $this->assertTrue(auth_expr_parse('(user2 || @group)')->evaluate());

        $this->assertFalse(auth_expr_parse('!(user || @group)')->evaluate());
        $this->assertFalse(auth_expr_parse('!(user || !@group)')->evaluate());
        $this->assertFalse(auth_expr_parse('!(!user || @group)')->evaluate());
        $this->assertTrue(auth_expr_parse('!(!user || !@group)')->evaluate());
        $this->assertTrue(auth_expr_parse('!(user2 || @group2)')->evaluate());
        $this->assertFalse(auth_expr_parse('!(user || @group2)')->evaluate());
        $this->assertFalse(auth_expr_parse('!(user2 || @group)')->evaluate());

        $this->assertTrue(auth_expr_parse('user, @group')->evaluate());
        $this->assertTrue(auth_expr_parse('user, !@group')->evaluate());
        $this->assertTrue(auth_expr_parse('!user, @group')->evaluate());
        $this->assertFalse(auth_expr_parse('!user, !@group')->evaluate());
        $this->assertFalse(auth_expr_parse('user2, @group2')->evaluate());
        $this->assertTrue(auth_expr_parse('user, @group2')->evaluate());
        $this->assertTrue(auth_expr_parse('user2, @group')->evaluate());

        auth_expr_evaluation_context()->SIMULATE_IN_GROUPS = null;
        auth_expr_evaluation_context()->SIMULATE_USERS = null;
    }

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
        $meta_file = __DIR__ . '/../conf/metadata.php';

        if (!file_exists($conf_file) && !file_exists($meta_file)) {
            self::markTestSkipped('No config files exist -> skipping test');
        }

        if (file_exists($conf_file)) {
            include($conf_file);
        }
        if (file_exists($meta_file)) {
            include($meta_file);
        }

        $this->assertEquals(
            gettype($conf),
            gettype($meta),
            'Both ' . DOKU_PLUGIN . 'ifauthex/conf/default.php and ' . DOKU_PLUGIN . 'ifauthex/conf/metadata.php have to exist and contain the same keys.'
        );

        if ($conf !== null && $meta !== null) {
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
