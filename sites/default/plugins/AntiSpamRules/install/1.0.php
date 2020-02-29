<?php
/**
 *
 * User: silva
 * Date: 2019-01-14
 * Time: 20:50
 */


if(!\CODOF\Util::optionExists('ASR_WORDS_NOT_ALLOWED_EVER')){
    \CODOF\Util::set_opt('ASR_WORDS_NOT_ALLOWED_EVER',"");
    \CODOF\Util::set_opt('ASR_BLOCK_TILL_POST_COUNT',"1");
    \CODOF\Util::set_opt('ASR_BLOCK_TILL_POST_COUNT_WORDS',"http://,https://");
}