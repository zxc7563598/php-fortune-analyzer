# hejunjie/fortune-analyzer

八字排盘工具库，支持 1900-2100 年间阳历与农历的相互转换，内置秒级精度的 24 节气时间计算，提供四柱（年、月、日、时）排盘、五行推演等核心命理功能。

---

## 初衷

在开发这款八字排盘工具时，我意识到市面上已经有许多相关的 PHP 库，但大部分的实现都存在一个共同问题：代码结构复杂且注释不足，导致理解和维护起来非常困难。对于需要用到命理分析的开发者来说，工具的正确性固然重要，但易用性和可读性同样是不可忽视的。

因此，我决定开发一个简洁、清晰、易于理解的八字排盘工具。每个方法的实现都在代码中进行了详细的注释，清楚地标明了每个步骤的计算逻辑和思路，确保使用者不仅能使用这个工具，还能理解它的内部机制。这不仅有助于开发者在使用过程中避免误解和错误，也让他们能够更好地定制或扩展功能。

在我看来，代码的清晰度远比复杂的结构更加重要。简单明了的接口和注释清晰的实现，可以让每一个开发者都能轻松上手并快速理解命理学的核心计算方法。希望这款工具不仅能帮助大家在实践中高效完成需求，也能作为学习和理解八字命理计算的一份良好资料。

## 主要功能

- 阳历与农历的相互转换，支持 1900-2100 年范围
- 精确计算 24 节气时间，秒级精度，由 [Jet Propulsion Laboratory](https://www.jpl.nasa.gov) 提供
- 四柱排盘（八字）计算，支持年柱、月柱、日柱、时柱
- 五行统计及五行局计算，辅助命理分析
- 后续版本计划支持十神分布、排大运、流年推算等功能

---

## 安装

使用 Composer 安装：

```bash
composer require hejunjie/fortune-analyzer
```

## 目前支持的方法

| 方法                                    | 说明                       |
| :-------------------------------------- | :------------------------- |
| FortuneAnalyzer::convertSolarToLunar()  | 阳历转农历                 |
| FortuneAnalyzer::convertLunarToSolar()  | 农历转阳历                 |
| FortuneAnalyzer::getSolarTerms()        | 获取指定年份的 24 节气时间 |
| FortuneAnalyzer::analyzeFourPillars()   | 获取八字                   |
| FortuneAnalyzer::getYearPillar()        | 获取年柱                   |
| FortuneAnalyzer::getMonthPillar()       | 获取月柱                   |
| FortuneAnalyzer::getDayPillar()         | 获取日柱                   |
| FortuneAnalyzer::getHourPillar()        | 获取时柱                   |
| FortuneAnalyzer::getWuXingBreakdown()   | 获取五行信息               |
| FortuneAnalyzer::analyzeWuXingSimple()  | 获取五行统计（不含藏干）   |
| FortuneAnalyzer::analyzeWuXingFull()    | 获取五行统计（包含藏干）   |
| FortuneAnalyzer::detectWuXingJu()       | 获取五行局                 |
| FortuneAnalyzer::getShiShenDistribution | 计算十神                   |
| FortuneAnalyzer::interpretShiShen       | 分析十神                   |

## 快速开始

```php
<?php

use Hejunjie\FortuneAnalyzer\FortuneAnalyzer;

$year = '1997';
$date = '1997-01-21 16:30:00';

// 阳历转农历
$convertSolarToLunar = FortuneAnalyzer::convertSolarToLunar($date);
// 1996-12-13

// 农历转阳历
$convertLunarToSolar = FortuneAnalyzer::convertLunarToSolar($date);
// 1997-02-27

// 获取指定年份的 24 节气时间（秒级精度）
$getSolarTerms = FortuneAnalyzer::getSolarTerms($year);
// {
//     "小寒": "1997-01-05 15:24:29",
//     "大寒": "1997-01-20 08:42:31",
//     "立春": "1997-02-04 03:01:58",
//     "雨水": "1997-02-18 22:51:29",
//     "惊蛰": "1997-03-05 21:04:09",
//     "春分": "1997-03-20 21:54:40",
//     "清明": "1997-04-05 01:56:17",
//     "谷雨": "1997-04-20 09:02:49",
//     "立夏": "1997-05-05 19:19:28",
//     "小满": "1997-05-21 08:17:53",
//     "芒种": "1997-06-05 23:32:33",
//     "夏至": "1997-06-21 16:19:57",
//     "小暑": "1997-07-07 09:49:23",
//     "大暑": "1997-07-23 03:15:26",
//     "立秋": "1997-08-07 19:36:18",
//     "处暑": "1997-08-23 10:19:11",
//     "白露": "1997-09-07 22:28:49",
//     "秋分": "1997-09-23 07:55:46",
//     "寒露": "1997-10-08 14:05:10",
//     "霜降": "1997-10-23 17:14:46",
//     "立冬": "1997-11-07 17:14:39",
//     "小雪": "1997-11-22 14:47:34",
//     "大雪": "1997-12-07 10:04:53",
//     "冬至": "1997-12-22 04:07:02"
// }



// 获取八字
$analyzeFourPillars = FortuneAnalyzer::analyzeFourPillars($date);
// [
//     "丙子",
//     "辛丑",
//     "癸亥",
//     "庚申"
// ]

// 获取年柱
$getYearPillar = FortuneAnalyzer::getYearPillar($date);
// {
//     "tiangan": "丙",
//     "dizhi": "子"
// }

// 获取月柱
$getMonthPillar = FortuneAnalyzer::getMonthPillar($date);
// {
//     "tiangan": "辛",
//     "dizhi": "丑"
// }

// 获取日柱
$getDayPillar = FortuneAnalyzer::getDayPillar($date);
// {
//     "tiangan": "癸",
//     "dizhi": "亥"
// }

// 获取时柱
$getHourPillar = FortuneAnalyzer::getHourPillar($date);
// {
//     "tiangan": "庚",
//     "dizhi": "申"
// }

// 获取五行信息
$getWuXingBreakdown = FortuneAnalyzer::getWuXingBreakdown($analyzeFourPillars);
// {
//     "tiangan": [
//         {
//             "丙": "火"
//         },
//         {
//             "辛": "金"
//         }
//     ],
//     "dizhi": [
//         {
//             "子": "水"
//         },
//         {
//             "丑": "土"
//         }
//     ],
//     "canggan": [
//         {
//             "壬": "水",
//             "甲": "木"
//         },
//         {
//             "庚": "金",
//             "壬": "水",
//             "戊": "土"
//         }
//     ]
// }

// 获取五行统计（不含藏干）
$analyzeWuXingSimple = FortuneAnalyzer::analyzeWuXingSimple($analyzeFourPillars);
// {
//     "金": 3,
//     "木": 0,
//     "水": 3,
//     "火": 1,
//     "土": 1
// }

// 获取五行统计（包含藏干）
$analyzeWuXingFull = FortuneAnalyzer::analyzeWuXingFull($analyzeFourPillars);
// {
//     "金": 5,
//     "木": 1,
//     "水": 7,
//     "火": 1,
//     "土": 3
// }

// 获取五行局
$detectWuXingJu = FortuneAnalyzer::detectWuXingJu($analyzeFourPillars);
// {
//     "main_ju": "水三会",
//     "description": "亥、子、丑相聚，北方水旺成象，主聪慧机敏、擅长谋略。",
//     "extra": {
//         "wuju": [
//             "水二局"
//         ],
//         "sanhe": [
//
//         ],
//         "liuhe": [
//             "水六合"
//         ]
//     }
// }

// 计算十神
$getShiShenDistribution = FortuneAnalyzer::getShiShenDistribution($analyzeFourPillars);
// {
//     "dayGan": "庚",
//     "dayGanAttr": "阳金",
//     "shiShenDistribution": {
//         "yearPillar": {
//             "tiangan": [
//                 "丁",
//                 "七杀"
//             ],
//             "dizhi": [
//                 "丑",
//                 "偏印"
//             ]
//         },
//         "monthPillar": {
//             "tiangan": [
//                 "己",
//                 "偏印"
//             ],
//             "dizhi": [
//                 "酉",
//                 "劫财"
//             ]
//         },
//         "dayPillar": {
//             "tiangan": [
//                 "庚",
//                 "日主"
//             ],
//             "dizhi": [
//                 "午",
//                 "七杀"
//             ]
//         },
//         "hourPillar": {
//             "tiangan": [
//                 "甲",
//                 "正财"
//             ],
//             "dizhi": [
//                 "申",
//                 "比肩"
//             ]
//         }
//     }
// }

// 分析十神
$interpretShiShen = FortuneAnalyzer::interpretShiShen($analyzeFourPillars);
// {
//     "frequency": {
//         "劫财": 1,
//         "伤官": 2,
//         "偏财": 1,
//         "正印": 1,
//         "比肩": 1,
//         "正财": 1
//     },
//     "statistics": {
//         "印星": 1,
//         "比劫": 2,
//         "食伤": 2,
//         "官杀": 0,
//         "财星": 2
//     },
//     "analysis": [
//         "食伤旺盛，思维活跃，适合技艺表达之道，但易言多惹祸。",
//         "财星旺，擅长理财，注重物质生活，但需防贪欲过重。",
//         "比劫强，个性独立，但容易固执争斗，兄弟缘深也易有竞争。",
//         "命局较为均衡，需结合大运流年来综合分析喜忌。"
//     ]
// }
```
