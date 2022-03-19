# DLE-BlockProLight
![Release version](https://img.shields.io/github/v/release/dle-modules/DLE-BlockProLight?style=flat-square&v=1)
![DLE](https://img.shields.io/badge/DLE-15.x-green.svg?style=flat-square "DLE Version")
![License](https://img.shields.io/github/license/dle-modules/DLE-BlockProLight?style=flat-square)

Облегченная версия модуля DLE-BlockPro для замены вывода новостей через shortstory.
### Внимание!
Модуль работает только для вывода кратких новостей (вместо shortstory.tpl)

## Установка модуля
1. Установить модуль [DLE-BlockPro](https://github.com/dle-modules/DLE-BlockPro/releases/latest) (требуется для работы).
2. Устанавливаем как обычный плагин, файл **blockpro_light_plugin.zip** содержит всё необходимое для автоматической установки.

## Использование модуля
Для перевода кратких новостей на вывод через модуль blockpro достаточно прописать в `shortstory.tpl` следующий код:
```
{blockpro-light}
```

Для того, что бы передать дополнительные параметры в модуль, необходимо использовать такой тег:

```
{blockpro-light params="param=value&param1=value1"}
```
Где **param=value&param1=value1** - параметры стандартной строки подключения модуля.
Например для отключения кеша и вывода статистики работы модуля в BlockPro используется такая строка подключения:
```
{include file="engine/modules/base/blockpro.php?nocache=y&showstat=y"}
```
А тег в BlockProLight будет таким:
```
{blockpro-light params="nocache=y&showstat=y"}
```
