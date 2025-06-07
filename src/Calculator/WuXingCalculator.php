<?php

namespace Hejunjie\FortuneAnalyzer\Calculator;

class WuXingCalculator
{

    /**
     * 天干与五行的对应关系
     *
     * 10天干与五行的映射关系，用于判断天干所属的五行属性。
     * 甲乙属木，丙丁属火，戊己属土，庚辛属金，壬癸属水。
     */
    protected static array $tianganWuxing = [
        '甲' => '木',
        '乙' => '木',
        '丙' => '火',
        '丁' => '火',
        '戊' => '土',
        '己' => '土',
        '庚' => '金',
        '辛' => '金',
        '壬' => '水',
        '癸' => '水',
    ];

    /**
     * 地支与五行的对应关系
     *
     * 12地支对应的五行属性。用于判断地支所属的五行类型。
     */
    protected static array $dizhiWuxing = [
        '子' => '水',
        '丑' => '土',
        '寅' => '木',
        '卯' => '木',
        '辰' => '土',
        '巳' => '火',
        '午' => '火',
        '未' => '土',
        '申' => '金',
        '酉' => '金',
        '戌' => '土',
        '亥' => '水'
    ];

    /**
     * 地支藏干对应关系
     *
     * 地支中的藏干（内含天干），部分地支藏有多个天干。
     * 用于提取地支内部隐藏的天干信息，便于五行详细分析。
     */
    protected static array $cangGanMap = [
        '子' => ['癸'],
        '丑' => ['己', '癸', '辛'],
        '寅' => ['甲', '丙', '戊'],
        '卯' => ['乙'],
        '辰' => ['戊', '乙', '癸'],
        '巳' => ['丙', '庚', '戊'],
        '午' => ['丁', '己'],
        '未' => ['己', '丁', '乙'],
        '申' => ['庚', '壬', '戊'],
        '酉' => ['辛'],
        '戌' => ['戊', '辛', '丁'],
        '亥' => ['壬', '甲']
    ];

    /**
     * 五局（五行局）对应的地支组合
     *
     * 五局根据地支的不同组合划分，代表命盘中五行的主导区域。
     * 五局名称中的数字代表该局包含的地支数量。
     */
    protected static array $fiveJu = [
        '金四局' => ['申', '酉'],
        '木三局' => ['寅', '卯'],
        '水二局' => ['亥', '子'],
        '火六局' => ['巳', '午'],
        '土五局' => ['辰', '戌', '丑', '未'],
    ];

    /**
     * 三会局
     *
     * 三会局由三个地支组成的组合，代表特定五行的聚合。
     * 三会是八字命理中重要的组合，反映五行气场的强化。
     */
    protected static array $sanHuiJu = [
        '木三会' => ['寅', '卯', '辰'],
        '火三会' => ['巳', '午', '未'],
        '金三会' => ['申', '酉', '戌'],
        '水三会' => ['亥', '子', '丑'],
    ];

    /**
     * 三合局
     *
     * 三合局由三个地支组成的和合组合，通常用来判定五行的互生互旺关系。
     * 三合局对八字结构的调和和五行气的流通具有重要作用。
     */
    protected static array $sanHeJu = [
        '水三合' => ['申', '子', '辰'],
        '火三合' => ['寅', '午', '戌'],
        '木三合' => ['亥', '卯', '未'],
        '金三合' => ['巳', '酉', '丑'],
    ];

    /**
     * 六合局
     *
     * 六合局由两地支组成的合局，是最基本的地支合局之一，
     * 代表五行之间的调和和平衡关系，常用于辅助判定。
     */
    protected static array $liuHeJu = [
        '土六合' => ['丑', '未'],
        '金六合' => ['申', '酉'],
        '木六合' => ['寅', '亥'],
        '水六合' => ['子', '丑'],
        '火六合' => ['巳', '午'],
    ];


    /**
     * 各五行局的说明文本
     *
     * 对应五局、三会、三合、六合等的文字说明，
     * 便于展示和理解不同五行局的性格特征和象征意义。
     */
    protected static array $descriptions = [
        '金四局' => '命盘中出现申、酉等金旺之地支，金气成象，主刚毅果决、重信重义。',
        '木三局' => '命盘中出现寅、卯等木旺之地支，木气成象，主仁慈聪慧、有生发之机。',
        '水二局' => '命盘中出现亥、子等水旺之地支，水气成象，主智慧灵活、应变强。',
        '火六局' => '命盘中出现巳、午等火旺之地支，火气成象，主热情主动、光明磊落。',
        '土五局' => '命盘中出现辰、戌、丑、未等地支，土气成象，主稳重厚道、有守成之力。',
        '木三会' => '命盘中寅、卯、辰齐全，东方木旺成象，主仁德聪颖、善于开创。',
        '火三会' => '巳、午、未三支齐聚，南方火旺成象，主热情积极、有领导力。',
        '金三会' => '申、酉、戌汇聚，西方金旺成象，主果断果敢、讲信守义。',
        '水三会' => '亥、子、丑相聚，北方水旺成象，主聪慧机敏、擅长谋略。',
        '水三合' => '命盘中有申、子、辰三支，水气流通旺盛，主智慧过人、通权达变。',
        '火三合' => '寅、午、戌三支成合，火气旺盛，主热情勇敢、具领袖气质。',
        '木三合' => '亥、卯、未三支成合，木气旺盛，主仁厚有礼、善于发展。',
        '金三合' => '巳、酉、丑三支成合，金气充足，主果决干练、意志坚定。',
        '土六合' => '丑与未相合，土气厚重，主忠实稳重、踏实守信。',
        '金六合' => '申与酉相合，金气精纯，主聪明有谋、精于判断。',
        '木六合' => '寅与亥相合，木气生发，主仁义聪慧、富有远见。',
        '水六合' => '子与丑相合，水气通达，主灵动多谋、性格柔和。',
        '火六合' => '巳与午相合，火气明盛，主热情活泼、富有激情。'
    ];

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
            $elements[self::$tianganWuxing[$tg]]++;
            $elements[self::$dizhiWuxing[$dz]]++;
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
            $elements[self::$tianganWuxing[$tg]]++;
            $elements[self::$dizhiWuxing[$dz]]++;
            if (isset(self::$cangGanMap[$dz])) {
                foreach (self::$cangGanMap[$dz] as $hiddenGan) {
                    $elements[self::$tianganWuxing[$hiddenGan]]++;
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
            $result['tiangan'][] = [$tg => self::$tianganWuxing[$tg] ?? null];
            // 地支五行
            $result['dizhi'][] = [$dz => self::$dizhiWuxing[$dz] ?? null];
            // 藏干五行
            $hidden = [];
            foreach (self::$cangGanMap[$dz] ?? [] as $gan) {
                $hidden[$gan] = self::$tianganWuxing[$gan] ?? null;
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
        foreach (self::$sanHuiJu as $name => $group) {
            if (count(array_intersect($branches, $group)) === 3) {
                $result['三会局'][] = $name;
            }
        }
        // 三合局判定（严格匹配）
        foreach (self::$sanHeJu as $name => $group) {
            if (count(array_intersect($branches, $group)) === 3) {
                $result['三合局'][] = $name;
            }
        }
        // 六合局判定（成对匹配）
        foreach (self::$liuHeJu as $name => $pair) {
            if (count(array_intersect($branches, $pair)) === 2) {
                $result['六合局'][] = $name;
            }
        }
        // 五局判定（依赖三会局或三合局）
        foreach (self::$fiveJu as $juName => $juBranches) {
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
            'description' => self::$descriptions[$mainJu] ?? '',
            'extra' => [
                'wuju' => array_values(array_diff($result['五局'], [$mainJu])),
                'sanhe' => array_values(array_diff($result['三合局'], [$mainJu])),
                'liuhe' => $result['六合局'],
            ]
        ];
    }
}
