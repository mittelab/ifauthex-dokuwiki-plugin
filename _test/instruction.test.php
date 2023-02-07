<?php

/**
 * General tests for the ifauthex plugin
 *
 * @group plugin_ifauthex
 * @group plugins
 */
class instructions_plugin_ifauthex_test extends DokuWikiTest
{

    protected $pluginsEnabled = array('ifauthex');


    public function test_instructions()
    {

        $calls = p_get_instructions(file_get_contents(__DIR__.'/testpage.txt'));

        $calls = array_map([self::class, 'stripByteIndex'], $calls);

        $this->assertJsonStringEqualsJsonFile(__DIR__.'/testpage.json', json_encode($calls));

        //print_r(json_encode($calls));
    }

    public function test_nested_instructions()
    {

        $calls = p_get_instructions(file_get_contents(__DIR__.'/testpagenested.txt'));

        $calls = array_map([self::class, 'stripByteIndex'], $calls);

        $this->assertJsonStringEqualsJsonFile(__DIR__.'/testpagenested.json', json_encode($calls));

        // print_r(json_encode($calls));
    }

    /**
     * copied from the core test suite, removes the byte positions
     *
     * @param $call
     * @return mixed
     */
    public static function stripByteIndex($call) {
        unset($call[2]);
        if ($call[0] == "nest") {
            $call[1][0] = array_map('stripByteIndex',$call[1][0]);
        }
        return $call;
    }
}
