<?php
/*
=============================================================================
BLockProLight
=============================================================================
Автор:   ПафНутиЙ
URL:     https://git.io/JflGt
=============================================================================
 */

if (!defined('DATALIFEENGINE')) {
    header("HTTP/1.1 403 Forbidden");
    header('Location: ../../');
    die("Hacking attempt!");
}

/**
 * @global array $config
 * @global array $member_id
 * @global array $lang
 * @global array $category_id
 * @global array $user_group
 */

/** @var bool $showstat */
if ($showstat) {
    $start = microtime(true);
}
/**
 * Конфиг модуля
 *
 * @global array $isAjaxConfig
 */
include ENGINE_DIR.'/modules/base/core/config.php';

global $bpNewsList, $cache_id, $cache_prefix;

$cfg['bpNewsList'] = !empty($bpNewsList) && is_array($bpNewsList) ? $bpNewsList : [];

include ENGINE_DIR.'/data/blockpro.php';

include_once ENGINE_DIR.'/classes/plugins.class.php';

// Объединяем массивы конфигов
/** @var array $bpConfig */
$cfg = array_merge($cfg, $bpConfig);

// Сохраняем текущее значение переменной, если она задана (fix #142)
$startCacheNameAddon = $cfg['cacheNameAddon'];

$cfg['cacheNameAddon']   = [];
$cfg['cacheNameAddon'][] = $startCacheNameAddon;


// Переменные для формирования кеша
$cfg['cacheVars'] .= ',category,do,cstart,cat,main';

if ($cfg['cacheVars']) {
    // Если установлена переменная, добавим в имя кеша требуемые дополнения
    // Убираем пробелы, на всякий пожарный
    $cfg['cacheVars'] = str_replace(' ', '', $cfg['cacheVars']);
    // Разбиваем строку на массив
    $arCacheVars = explode(',', $cfg['cacheVars']);
    foreach ($arCacheVars as $cacheVar) {
        // Сверяем данные из массива с данными, доступными на странице
        if (isset($_REQUEST[$cacheVar])) {
            $cfg['cacheNameAddon'][] = $_REQUEST[$cacheVar].$cacheVar.'_';
        }
        if ($dle_module == $cacheVar) {
            $cfg['cacheNameAddon'][] = $dle_module.'_';
        }
    }
}


// Поддержка модуля multiLang
$multiLangEnabled = false;
// По умолчанию язык пустой
$langVariant = '';
// Задаём пустой массив для конфига модуля MultiLanguage
$lang_config = [];
// Получаем список доступных языков в виде массива
$langList                  = explode('|', $cfg['langList']);
$multiLangAdditionalFields = $multiLangNewsFields = [];

if ($cfg['multiLang'] && isset($_REQUEST['lang'])) {
    $requestLang = trim($_REQUEST['lang']);

    // Если язык доступен, работаем
    if (in_array($requestLang, $langList)) {
        // Импортируем конфиг модуля MultiLanguage
        include(DLEPlugins::Check(ENGINE_DIR.'/data/multilanguage_config.php'));

        // Если модуль включен, работаем
        if ($lang_config['mod_on']) {
            // Добавляем параметры в кеш
            $cfg['cacheNameAddon'][] = 'multiLang_'.$_REQUEST['lang'].'_';
            // Устанавливаем нужный язык для дальнейшего использования в запросах
            $langVariant      = $requestLang;
            $multiLangEnabled = true;

            $multiLangAdditionalFields = [
                '1' => 'p.title_'.$langVariant,
                '2' => 'p.short_story_'.$langVariant,
                '3' => 'p.full_story_'.$langVariant,
            ];

            $multiLangNewsFields = [
                '1' => [0 => 'title', 1 => 'title_'.$langVariant],
                '2' => [0 => 'short_story', 1 => 'short_story_'.$langVariant],
                '3' => [0 => 'full_story', 1 => 'full_story_'.$langVariant],
            ];

            $multiLngEnabledFields = explode(',', $lang_config['fields_news']);
        }
    }
}

$cfg['cacheNameAddon'] = array_filter($cfg['cacheNameAddon']);
// Удаляем дублирующиеся значения кеша. Может возникать при AJAX вызове с &catId=this
$cfg['cacheNameAddon'] = array_unique($cfg['cacheNameAddon']);
$cfg['cacheNameAddon'] = implode('_', $cfg['cacheNameAddon']);

if ($cfg['cacheLive']) {
    // Меняем префикс кеша для того, чтобы он не чистился автоматически, если указано время жизни кеша.
    $cfg['cachePrefix'] = 'base';
}

// Определяемся с правильным шаблоном сайта (для этого модуля он всегда равен текущему `$config['skin']`)
$currentSiteSkin = $config['skin'];

// Формируем имя кеша
$cacheName = implode('_', $cfg).$currentSiteSkin;

// Определяем необходимость создания кеша для разных групп
$cacheSuffix      = ($cfg['cacheSuffixOff']) ? false : true;
$clear_time_cache = false;
// Если установлено время жизни кеша
if ($cfg['cacheLive']) {
    // Формируем имя кеш-файла в соответствии с правилами формирования такового стандартными средствами DLE, для последующей проверки на существование этого файла.
    $_end_file = (!$cfg['cacheSuffixOff']) ? ($is_logged) ? '_'.$member_id['user_group'] : '_0' : false;
    $filedate  = ENGINE_DIR.'/cache/'.$cfg['cachePrefix'].'_'.md5($cacheName).$_end_file.'.tmp';

    // Определяем в чём измеять время жизни кеша, в минутах или секундах
    $cacheLiveTimer = (strpos($cfg['cacheLive'], 's')) ? (int)$cfg['cacheLive'] : $cfg['cacheLive'] * 60;

    if (@file_exists($filedate)) {
        $cache_time = time() - @filemtime($filedate);
    } else {
        $cache_time = $cacheLiveTimer;
    }
    if ($cache_time >= $cacheLiveTimer) {
        $clear_time_cache = true;
    }
}

$output = false;

// Массив для записи возникающих ошибок
$outputLog = [
    'errors' => [],
    'info'   => [],
];

// Если nocache не установлен - пытаемся вывести данные из кеша.
if (!$cfg['nocache']) {
    $output = dle_cache($cfg['cachePrefix'], $cacheName, $cacheSuffix);
}
// Сбрасываем данные, если истекло время жизни кеша
if ($clear_time_cache) {
    $output = false;
}

if (!$output) {

    // Подключаем всё необходимое
    include_once(DLEPlugins::Check(ENGINE_DIR.'/modules/base/core/base.php'));

    // Вызываем ядро
    $base = new base();

    // Назначаем конфиг модуля
    $base->cfg = $base->setConfig($cfg);

    // Назначаем текущий шаблон сайта
    $base->dle_config['skin'] = $currentSiteSkin;

    // Пустой массив для конфга шаблонизатора.
    $tplOptions = [];

    // Если кеширование блока отключено - будем автоматически проверять скомпилированный шаблон на изменения.
    if ($base->cfg['nocache']) {
        $tplOptions['auto_reload'] = true;
    }

    // Подключаем опции шаблонизатора
    $base->tplOptions = $base->setConfig($tplOptions);
    // Подключаем шаблонизатор
    $base->getTemplater($base->tplOptions);


    // Добавляем глобавльный тег $.blockPro
    $base->tpl->addAccessorSmart('blockPro', 'block_pro', Fenom::ACCESSOR_PROPERTY);
    $base->tpl->block_pro = $base;

    if ($base->cfg['navDefaultGet']) {
        $base->cfg['pageNum'] = (isset($_GET['cstart']) && (int)$_GET['cstart'] > 0) ? (int)$_GET['cstart'] : 1;
    }

    // Обрабатываем данные функцией stripslashes рекурсивно.
    $list = stripSlashesInArray($bpNewsList);

    // Путь к папке с текущим шаблоном
    $tplArr['theme'] = $base->dle_config['http_home_url'].'templates/'.$base->dle_config['skin'];

    // Делаем доступным конфиг DLE внутри шаблона
    $tplArr['dleConfig'] = $base->dle_config;

    // Делаем доступной переменную $dle_module в шаблоне
    $tplArr['dleModule'] = $dle_module;

    // Делаем доступной переменную $lang в шаблоне
    $tplArr['lang']        = $lang;
    $tplArr['cacheName']   = $cacheName;
    $tplArr['category_id'] = $category_id;
    $tplArr['cfg']         = $cfg;
    $tplArr['langVariant'] = $langVariant;
    // Массив для аттачей и похожих новостей.
    $attachments = $relatedIds = [];

    // Обрабатываем данные в массиве.
    foreach ($list as $key => &$newsItem) {
        // Плучаем обработанные допполя.
        $newsItem['xfields'] = stripSlashesInArray(xfieldsdataload($newsItem['xfields']));
        // Собираем массив вложений
        $attachments[] = $relatedIds[] = $newsItem['id'];

        // Массив данных для формирования ЧПУ
        $urlArr = [
            'category' => $newsItem['category'],
            'id'       => $newsItem['id'],
            'alt_name' => $newsItem['alt_name'],
            'date'     => $newsItem['date'],
        ];
        // Записываем сформированный URL статьи в массив
        $newsItem['url'] = $base->getPostUrl($urlArr, $langVariant);

        // Присваиваем полям необходимые значения в зависимости от языка
        if ($multiLangEnabled) {
            foreach ($multiLngEnabledFields as $enabledField) {
                $newsItem[$multiLangNewsFields[$enabledField][0]] = $newsItem[$multiLangNewsFields[$enabledField][1]];
            }
        }

        // Добавляем тег edit
        if ($is_logged and (($member_id['name'] == $newsItem['autor']
                    and $user_group[$member_id['user_group']]['allow_edit'])
                or $user_group[$member_id['user_group']]['allow_all_edit'])
        ) {
            $_SESSION['referrer']    = $_SERVER['REQUEST_URI'];
            $newsItem['allow_edit']  = true;
            $newsItem['editOnclick'] = 'onclick="return dropdownmenu(this, event, MenuNewsBuild(\''.$newsItem['id']
                .'\', \'short\'), \'170px\')"';

        } else {
            $newsItem['allow_edit']  = false;
            $newsItem['editOnclick'] = '';
        }

        // Записываем сформированные теги в массив
        $newsItem['tags'] = $base->tagsLink($newsItem['tags']);

        // Записываем в массив ссылку на аватар
        $newsItem['avatar'] = $tplArr['theme'].'/dleimages/noavatar.png';
        // А если у юзера есть фотка - выводим её, или граватар.
        if ($newsItem['foto']) {
            $userFoto = $newsItem['foto'];
            if (count(explode('@', $userFoto)) == 2) {
                $newsItem['avatar'] = '//www.gravatar.com/avatar/'.md5(trim($userFoto)).'?s='
                    .intval($user_group[$newsItem['user_group']]['max_foto']);
            } else {
                $userFotoWHost = (strpos($userFoto, '//') === 0) ? 'http:'.$userFoto : $userFoto;
                $arUserFoto    = parse_url($userFotoWHost);
                if ($arUserFoto['host']) {
                    $newsItem['avatar'] = $userFoto;
                } else {
                    $newsItem['avatar'] = $base->dle_config['http_home_url'].'uploads/fotos/'.$userFoto;
                }
                unset($arUserFoto, $userFotoWHost);
            }
        }

        // Разбираемся с рейтингом
        $newsItem['showRating']      = '';
        $newsItem['showRatingCount'] = '';
        if ($newsItem['allow_rate']) {
            $newsItem['showRatingCount'] = '<span class="ignore-select" data-vote-num-id="'.$newsItem['id'].'">'
                .$newsItem['vote_num'].'</span>';
            $jsRAteFunctionName          = 'base_rate';

            if ($base->dle_config['short_rating'] and $user_group[$member_id['user_group']]['allow_rating']) {
                $newsItem['showRating'] = baseShowRating($newsItem['id'], $newsItem['rating'], $newsItem['vote_num'],
                    1);

                $newsItem['ratingOnclickPlus']  = 'onclick="'.$jsRAteFunctionName.'(\'plus\', \''.$newsItem['id']
                    .'\'); return false;"';
                $newsItem['ratingOnclickMinus'] = 'onclick="'.$jsRAteFunctionName.'(\'minus\', \''.$newsItem['id']
                    .'\'); return false;"';

            } else {
                $newsItem['showRating'] = baseShowRating($newsItem['id'], $newsItem['rating'], $newsItem['vote_num'],
                    0);

                $newsItem['ratingOnclickPlus']  = '';
                $newsItem['ratingOnclickMinus'] = '';
            }
        }
        // Разбираемся с избранным
        $newsItem['favorites'] = '';
        if ($is_logged) {
            $fav_arr = explode(',', $member_id['favorites']);

            if (!in_array($newsItem['id'], $fav_arr) || $base->dle_config['allow_cache']) {
                $newsItem['favorites'] = '<img data-favorite-id="'.$newsItem['id'].'" data-action="plus" src="'
                    .$tplArr['theme']
                    .'/dleimages/plus_fav.gif"  title="Добавить в свои закладки на сайте" alt="Добавить в свои закладки на сайте">';
            } else {
                $newsItem['favorites'] = '<img data-favorite-id="'.$newsItem['id'].'" data-action="minus" src="'
                    .$tplArr['theme']
                    .'/dleimages/minus_fav.gif"  title="Удалить из закладок" alt="Удалить из закладок">';
            }
        }
    }

    // Полученный массив с данными для обработки в шаблоне
    $tplArr['list'] = $list;

    // Определяем группу пользователя
    $tplArr['member_group_id'] = $member_id['user_group'];

    // Устанавливаем пустое значение для постранички по умолчанию.
    $tplArr['pages'] = '';
    // Общее кол-во новостей без постранички.
    $tplArr['totalCount'] = count($list);

    // Устанавливаем уникальный ID для блока по умолчанию
    $tplArr['block_id'] = 'bp_'.crc32(implode('_', $base->cfg));

    // Результат обработки шаблона
    try {
        $output = $base->tpl->fetch($base->dle_config['skin'].'/'.$base->cfg['template'].'.tpl', $tplArr);
    } catch (Exception $e) {
        $outputLog['errors'][] = $e->getMessage();
        $base->cfg['nocache']  = true;
    }

    // Если есть ошбки и включен вывод статистики — оключаем кеш.
    if (count($outputLog['errors']) > 0 && $cfg['showstat']) {
        $base->cfg['nocache'] = true;
    }

    // Создаём кеш, если требуется
    if (!$base->cfg['nocache']) {
        create_cache($base->cfg['cachePrefix'], $output, $cacheName, $cacheSuffix);
    }


}

// Обрабатываем вложения
/** @var $base */
if ($base->dle_config['files_allow']) {
    if (strpos($output, '[attachment=') !== false) {
        /** @var array $attachments */
        $output = show_attach($output, $attachments);
    }
} else {
    $output = preg_replace("'\[attachment=(.*?)\]'si", '', $output);
}

if ($user_group[$member_id['user_group']]['allow_hide']) {
    $output = str_ireplace('[hide]', '', str_ireplace('[/hide]', '', $output));
} else {
    $output = preg_replace('#\[hide\](.+?)\[/hide\]#ims', '', $output);
}

// Результат работы модуля
// Если блок не является внешним - выводим на печать
if (count($outputLog['errors']) > 0) {
    // Выводим ошибки, если они есть
    $outputErrors = [];
    $outputErrors[]
                  = '<ul class="bp-errors" style="border: solid 1px red; padding: 5px; margin: 5px 0; list-style: none; background: rgba(255,0,0,0.2)">';

    foreach ($outputLog['errors'] as $errorText) {
        $outputErrors[] = '<li>'.$errorText.'</li>';
    }
    $outputErrors[] = '</ul>';

    $outputErrors = implode('', $outputErrors);

    echo $outputErrors;
} else {
    // Если нет ошибок - выводим результат аботы модуля
    echo $output;
}

// Показываем стстаистику выполнения скрипта, если требуется
if ($cfg['showstat'] && $user_group[$member_id['user_group']]['allow_all_edit']) {

    // Информация об оперативке
    $mem_usg = (function_exists('memory_get_peak_usage')) ? '<br>Расход памяти: <b>'.round(memory_get_peak_usage()
            / (1024 * 1024), 2).'Мб </b>' : '';
    // Вывод статистики
    /** @var integer $start */
    echo '<div class="bp-statistics" style="border: solid 1px red; padding: 5px; margin: 5px 0;">Время выполнения скрипта: <b>'
        .round((microtime(true) - $start), 6).'</b> c.'.$mem_usg.'</div>';
}
