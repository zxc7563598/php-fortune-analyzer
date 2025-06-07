<?php

namespace Hejunjie\FortuneAnalyzer\Analysis;

use Hejunjie\FortuneAnalyzer\Calculator\BaZiCalculator;
use Hejunjie\FortuneAnalyzer\Converter\BaZiConstants;
use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class ShiShenAnalyzer
{

    /**
     * 获取四柱中每个天干和地支相对于日主天干的十神分布
     * 
     * 计算过程：
     * 1. 从四柱（年、月、日、时）中分别提取天干和地支。
     * 2. 以日主天干为基准，调用 BaZiCalculator::getShiShen 方法，
     *    计算其他天干和地支相对于日主的十神（如正财、偏印、比肩等）。
     * 3. 地支中藏干存在多个，默认取藏干的主气进行计算。
     * 4. 日主天干自身标注为“日主”，不调用十神计算。
     * 5. 最终返回一个数组，包含日主天干及其属性（阴阳五行），
     *    以及年、月、日、时柱天干和地支对应的十神分布。
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，每柱为两字字符串（天干+地支）
     * 
     * @return array 返回十神分布数组，结构中包含每柱的天干、地支及其对应的十神
     */
    public static function getShiShenDistribution(array $pillars): array
    {
        if (count($pillars) !== 4) {
            throw new \InvalidArgumentException("四柱数组必须包含4个元素（年柱、月柱、日柱、时柱）");
        }
        [$year, $month, $day, $hour] = $pillars;
        $yearGan = mb_substr($year, 0, 1);
        $yearZhi = mb_substr($year, 1, 1);
        $monthGan = mb_substr($month, 0, 1);
        $monthZhi = mb_substr($month, 1, 1);
        $dayGan = mb_substr($day, 0, 1); // 日主
        $dayZhi = mb_substr($day, 1, 1);
        $hourGan = mb_substr($hour, 0, 1);
        $hourZhi = mb_substr($hour, 1, 1);
        $result = [
            'dayGan' => $dayGan,
            'dayGanAttr' => BaZiConstants::TIANGAN_YINYANG[$dayGan] . BaZiConstants::TIANGAN_WUXING[$dayGan],
            'shiShenDistribution' => [
                'yearPillar' => [
                    'tiangan' => [$yearGan, BaZiCalculator::getShiShen($dayGan, $yearGan)],
                    'dizhi' => [$yearZhi, BaZiCalculator::getShiShen($dayGan, $yearZhi)],
                ],
                'monthPillar' => [
                    'tiangan' => [$monthGan, BaZiCalculator::getShiShen($dayGan, $monthGan)],
                    'dizhi' => [$monthZhi, BaZiCalculator::getShiShen($dayGan, $monthZhi)],
                ],
                'dayPillar' => [
                    'tiangan' => [$dayGan, '日主'],
                    'dizhi' => [$dayZhi, BaZiCalculator::getShiShen($dayGan, $dayZhi)],
                ],
                'hourPillar' => [
                    'tiangan' => [$hourGan, BaZiCalculator::getShiShen($dayGan, $hourGan)],
                    'dizhi' => [$hourZhi, BaZiCalculator::getShiShen($dayGan, $hourZhi)],
                ],
            ]
        ];
        return $result;
    }

    /**
     * 解析四柱八字中的十神分布，统计频次并输出简要解读
     *
     * 计算过程：
     * 1. 从四柱数组中提取每柱的天干和地支。
     * 2. 根据日主天干，调用 BaZiCalculator::getShiShen 计算每个天干和地支对应的十神。
     * 3. 统计各十神出现频率，并按印星、比劫、食伤、官杀、财星五类归类统计。
     * 4. 根据五类统计结果，给出简单的性格和命运倾向解读。
     * 5. 根据十神的帮扶或消耗情况，初步判断命主日元强弱及喜用神趋势。
     *
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，每柱为两字字符串（天干+地支）
     * 
     * @return array 返回包含十神频次统计、五类统计及简要分析的结果数组
     */
    public static function interpretShiShen(array $pillars): array
    {
        if (count($pillars) !== 4) {
            throw new \InvalidArgumentException("四柱数组必须包含4个元素（年柱、月柱、日柱、时柱）");
        }
        [$year, $month, $day, $hour] = $pillars;
        $yearGan = mb_substr($year, 0, 1);
        $yearZhi = mb_substr($year, 1, 1);
        $monthGan = mb_substr($month, 0, 1);
        $monthZhi = mb_substr($month, 1, 1);
        $dayGan = mb_substr($day, 0, 1); // 日主
        $dayZhi = mb_substr($day, 1, 1);
        $hourGan = mb_substr($hour, 0, 1);
        $hourZhi = mb_substr($hour, 1, 1);
        // 计算所有天干地支对应的十神
        $shiShenDist = [
            'yearPillar' => [
                'tiangan' => [$yearGan, BaZiCalculator::getShiShen($dayGan, $yearGan)],
                'dizhi' => [$yearZhi, BaZiCalculator::getShiShen($dayGan, $yearZhi)],
            ],
            'monthPillar' => [
                'tiangan' => [$monthGan, BaZiCalculator::getShiShen($dayGan, $monthGan)],
                'dizhi' => [$monthZhi, BaZiCalculator::getShiShen($dayGan, $monthZhi)],
            ],
            'dayPillar' => [
                'tiangan' => [$dayGan, '日主'],
                'dizhi' => [$dayZhi, BaZiCalculator::getShiShen($dayGan, $dayZhi)],
            ],
            'hourPillar' => [
                'tiangan' => [$hourGan, BaZiCalculator::getShiShen($dayGan, $hourGan)],
                'dizhi' => [$hourZhi, BaZiCalculator::getShiShen($dayGan, $hourZhi)],
            ],
        ];
        $frequency = [];
        $comment = [];
        // 统计十神频次
        foreach ($shiShenDist as $pillar => $parts) {
            foreach (['tiangan', 'dizhi'] as $part) {
                if (!isset($parts[$part][1])) continue;
                $shiShen = $parts[$part][1];
                if ($shiShen === '日主') continue;

                $frequency[$shiShen] = ($frequency[$shiShen] ?? 0) + 1;
            }
        }
        // 按类型归类统计
        $types = [
            '印星' => ['正印', '偏印'],
            '比劫' => ['比肩', '劫财'],
            '食伤' => ['食神', '伤官'],
            '官杀' => ['正官', '七杀'],
            '财星' => ['正财', '偏财'],
        ];
        $typeCount = [];
        foreach ($types as $type => $list) {
            $typeCount[$type] = array_sum(array_map(fn($s) => $frequency[$s] ?? 0, $list));
        }
        // 简单解读逻辑
        if (($typeCount['印星'] ?? 0) >= 2) {
            $comment[] = '命局印星偏旺，头脑灵活，易得长辈贵人扶持，但易内向依赖。';
        }
        if (($typeCount['官杀'] ?? 0) >= 2) {
            $comment[] = '官杀明显，主有责任心、受规矩约束，易遇压力、权力冲突。';
        }
        if (($typeCount['食伤'] ?? 0) >= 2) {
            $comment[] = '食伤旺盛，思维活跃，适合技艺表达之道，但易言多惹祸。';
        }
        if (($typeCount['财星'] ?? 0) >= 2) {
            $comment[] = '财星旺，擅长理财，注重物质生活，但需防贪欲过重。';
        }
        if (($typeCount['比劫'] ?? 0) >= 2) {
            $comment[] = '比劫强，个性独立，但容易固执争斗，兄弟缘深也易有竞争。';
        }
        // 喜用神初步判断
        $totalHelp = ($typeCount['印星'] ?? 0) + ($typeCount['比劫'] ?? 0);
        $totalDrain = ($typeCount['食伤'] ?? 0) + ($typeCount['财星'] ?? 0) + ($typeCount['官杀'] ?? 0);
        if ($totalHelp > $totalDrain + 1) {
            $comment[] = '命主日元偏强，适宜用官杀、财星为喜神，克泄调衡。';
        } elseif ($totalDrain > $totalHelp + 1) {
            $comment[] = '命主日元偏弱，适宜用比劫、印星为喜神以扶身。';
        } else {
            $comment[] = '命局较为均衡，需结合大运流年来综合分析喜忌。';
        }
        return [
            'frequency' => $frequency,
            'statistics' => $typeCount,
            'analysis' => $comment,
        ];
    }
}
