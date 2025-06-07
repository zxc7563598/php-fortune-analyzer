<?php

namespace Hejunjie\FortuneAnalyzer\Calculator;

use Hejunjie\FortuneAnalyzer\Converter\BaZiConstants;

class WuXingCalculator
{

    /**
     * 获取八字中五行元素的分布统计（不包含地支藏干部分）
     *
     * 该方法根据传入的出生时间字符串，推算出年柱、月柱、日柱、时柱的天干与地支，
     * 然后统计其中五行（金、木、水、火、土）的出现次数，仅包括：
     * - 四柱的天干（年、月、日、时）
     * - 四柱的地支（不展开藏干，仅按地支主五行计算）
     *
     * 示例输出：
     * [
     *   '金' => 2,
     *   '木' => 3,
     *   '水' => 1,
     *   '火' => 1,
     *   '土' => 1
     * ]
     *
     * @param array $fourPillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 BaZiCalculator::getFourPillars($date) 获取
     * 
     * @return array 返回五行统计数组，各五行出现次数
     */
    public static function getWuXingWithoutHidden(array $fourPillars): array
    {
        $elements = [
            '金' => 0,
            '木' => 0,
            '水' => 0,
            '火' => 0,
            '土' => 0
        ];
        // 年月日时的天干
        foreach ($fourPillars as $pillar) {
            $tg = mb_substr($pillar, 0, 1, 'UTF-8');
            $dz = mb_substr($pillar, 1, 1, 'UTF-8');
            $elements[BaZiConstants::TIANGAN_WUXING[$tg]]++;
            $elements[BaZiConstants::DIZHI_WUXING[$dz]]++;
        }
        return $elements;
    }

    /**
     * 获取八字中五行元素的分布统计（包含地支藏干部分）
     *
     * 此方法根据传入的四柱信息（年柱、月柱、日柱、时柱），统计五行（金、木、水、火、土）的出现次数。
     * 统计范围包括：
     * - 四柱的天干（年、月、日、时）
     * - 四柱的地支的主五行
     * - 四柱地支中所包含的藏干所对应的五行
     *
     * 示例输出：
     * [
     *   '金' => 3,
     *   '木' => 2,
     *   '水' => 2,
     *   '火' => 1,
     *   '土' => 2
     * ]
     *
     * @param array $fourPillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 BaZiCalculator::getFourPillars($date) 获取
     *
     * @return array 返回五行统计数组，每个五行对应其出现次数
     */
    public static function getWuXingWithHidden(array $fourPillars): array
    {
        $elements = [
            '金' => 0,
            '木' => 0,
            '水' => 0,
            '火' => 0,
            '土' => 0
        ];
        // 年月日时的天干 + 地支藏干
        foreach ($fourPillars as $pillar) {
            $tg = mb_substr($pillar, 0, 1, 'UTF-8');
            $dz = mb_substr($pillar, 1, 1, 'UTF-8');
            $elements[BaZiConstants::TIANGAN_WUXING[$tg]]++;
            $elements[BaZiConstants::DIZHI_WUXING[$dz]]++;
            if (isset(BaZiConstants::CANG_GAN_MAP[$dz])) {
                foreach (BaZiConstants::CANG_GAN_MAP[$dz] as $hiddenGan) {
                    $elements[BaZiConstants::TIANGAN_WUXING[$hiddenGan]]++;
                }
            }
        }
        return $elements;
    }

    /**
     * 获取四柱中各部分的五行详细信息
     *
     * 此方法根据传入的四柱（年柱、月柱、日柱、时柱），分别解析出：
     * - 天干对应的五行
     * - 地支对应的主五行
     * - 地支中藏干的所有天干及其对应五行
     *
     * 返回结构按柱顺序排列，格式如下：
     * [
     *   'tianga' => [
     *     ['甲' => '木'],
     *     ['乙' => '木'],
     *     ...
     *   ],
     *   'dizhi' => [
     *     ['子' => '水'],
     *     ['丑' => '土'],
     *     ...
     *   ],
     *   'canggan' => [
     *     ['癸' => '水'],
     *     ['己' => '土', '癸' => '水'],
     *     ...
     *   ]
     * ]
     *
     * @param array $fourPillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 BaZiCalculator::getFourPillars($date) 获取
     *
     * @return array 返回结构化的五行信息数组，包含天干、地支及藏干对应的五行属性
     */
    public static function getPillarDetailsSimple(array $fourPillars): array
    {
        $result = [
            'tiangan' => [],
            'dizhi' => [],
            'canggan' => []
        ];
        foreach ($fourPillars as $pillar) {
            $tg = mb_substr($pillar, 0, 1, 'UTF-8');
            $dz = mb_substr($pillar, 1, 1, 'UTF-8');
            // 天干五行
            $result['tiangan'][] = [$tg => BaZiConstants::TIANGAN_WUXING[$tg] ?? null];
            // 地支五行
            $result['dizhi'][] = [$dz => BaZiConstants::DIZHI_WUXING[$dz] ?? null];
            // 藏干五行
            $hidden = [];
            foreach (BaZiConstants::CANG_GAN_MAP[$dz] ?? [] as $gan) {
                $hidden[$gan] = BaZiConstants::TIANGAN_WUXING[$gan] ?? null;
            }
            $result['canggan'][] = $hidden;
        }
        return $result;
    }

    /**
     * 判定八字五行局（三会局 > 三合局 > 六合局 > 五局）
     *
     * 根据传入的四柱（年柱、月柱、日柱、时柱）地支，依次判断是否形成以下五行局：
     * - 三会局：严格匹配三个地支均属于同一三会组合
     * - 三合局：严格匹配三个地支均属于同一三合组合
     * - 六合局：成对匹配两个地支属于同一六合组合
     * - 五局：基于已形成的三会局或三合局推断对应的五局类型（金、木、水、火、土）
     *
     * 判定优先级依次为三会局 > 三合局 > 五局 > 六合局，最终返回主要的五行局及相关信息。
     *
     * 返回示例结构：
     * [
     *   'main_ju' => '金局',
     *   'description' => '金局的说明文字',
     *   'extra' => [
     *     'wuju' => [...],    // 其他非主要五局
     *     'sanhe' => [...],   // 三合局列表（非主要）
     *     'liuhe' => [...]    // 六合局列表
     *   ]
     * ]
     *
     * @param array $fourPillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 BaZiCalculator::getFourPillars($date) 获取
     *
     * @return array 返回五行局判定结果，包含主要五行局名称、描述及额外相关局的列表
     */
    public static function detectJu(array $fourPillars): array
    {
        $branches = [];
        foreach ($fourPillars as $pillar) {
            $branches[] = mb_substr($pillar, 1, 1, 'UTF-8');
        }
        $result = [
            '三会局' => [],
            '三合局' => [],
            '六合局' => [],
            '五局' => [],
        ];
        // 三会局判定（严格匹配）
        foreach (BaZiConstants::SAN_HUI_JU as $name => $group) {
            if (count(array_intersect($branches, $group)) === 3) {
                $result['三会局'][] = $name;
            }
        }
        // 三合局判定（严格匹配）
        foreach (BaZiConstants::SAN_HE_JU as $name => $group) {
            if (count(array_intersect($branches, $group)) === 3) {
                $result['三合局'][] = $name;
            }
        }
        // 六合局判定（成对匹配）
        foreach (BaZiConstants::LIU_HE_JU as $name => $pair) {
            if (count(array_intersect($branches, $pair)) === 2) {
                $result['六合局'][] = $name;
            }
        }
        // 五局判定（依赖三会局或三合局）
        foreach (BaZiConstants::WU_XING_JU as $juName => $juBranches) {
            $type = mb_substr($juName, 0, 1); // 金木水火土
            foreach (array_merge($result['三会局'], $result['三合局']) as $formedJu) {
                if (mb_strpos($formedJu, $type) === 0) {
                    $result['五局'][] = $juName;
                    break;
                }
            }
        }
        // 生成最终结构
        $mainJu = '';
        // 优先级：三会 > 三合 > 五局 > 六合
        if (!empty($result['三会局'])) {
            $mainJu = $result['三会局'][0];
        } elseif (!empty($result['三合局'])) {
            $mainJu = $result['三合局'][0];
        } elseif (!empty($result['五局'])) {
            $mainJu = $result['五局'][0];
        } elseif (!empty($result['六合局'])) {
            $mainJu = $result['六合局'][0];
        }
        return [
            'main_ju' => $mainJu,
            'description' => BaZiConstants::JU_DESCRIPTIONS[$mainJu] ?? '',
            'extra' => [
                'wuju' => array_values(array_diff($result['五局'], [$mainJu])),
                'sanhe' => array_values(array_diff($result['三合局'], [$mainJu])),
                'liuhe' => $result['六合局'],
            ]
        ];
    }
}
