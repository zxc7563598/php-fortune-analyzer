<?php

namespace Hejunjie\FortuneAnalyzer\Calculator;

use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class BaZiCalculator
{

    /**
     * 计算年柱
     * 
     * 根据指定阳历日期计算对应的八字年柱（天干地支组合）。
     * 年柱并非简单按年份划分，而是以“立春”节气作为分界：
     * 
     * - 若该日期早于当年立春，年柱应视为上一年的干支。
     * - 否则按当前年份干支计算。
     * 
     * 干支计算规则：
     * - 干支纪年以60年为一轮（甲子开始），公元4年为“甲子年”。
     * - 天干共有10个（甲乙丙丁戊己庚辛壬癸），按序循环取 `% 10`。
     * - 地支共有12个（子丑寅卯辰巳午未申酉戌亥），按序循环取 `% 12`。
     * 
     * 例如：
     *  - 输入 2025-02-02（立春前），应视为 2024 年，计算干支。
     *  - 输入 2025-02-05（立春后），视为 2025 年，计算干支。
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getYearPillar(string|\DateTimeInterface $date): array
    {
        $dt = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        $year = (int)$dt->format('Y');
        $solarTerms = DateConverter::getSolarTermsByYear($year);
        $lichun = isset($solarTerms['立春']) ? new \DateTime($solarTerms['立春']) : null;
        // 若未到立春，则使用上一年干支
        if ($lichun && $dt < $lichun) {
            $year--;
        }
        $heavenlyStems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $earthlyBranches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        $offset = ($year - 4) % 60; // 甲子年是公元4年
        return [
            'tiangan' => $heavenlyStems[$offset % 10],
            'dizhi' => $earthlyBranches[$offset % 12]
        ];
    }

    /**
     * 计算月柱
     * 
     * 根据指定阳历日期计算八字中的“月柱”（天干地支组合）。
     * 
     * 月柱的划分依据并非阳历月份，而是以24节气中的“节令”为起点。
     * 每个月干支从一个“节”开始（非中气），共用以下12个节令划分月份：
     * 
     *   正月起于立春 → 月支为寅
     *   二月起于惊蛰 → 月支为卯
     *   三月起于清明 → 月支为辰
     *   四月起于立夏 → 月支为巳
     *   五月起于芒种 → 月支为午
     *   六月起于小暑 → 月支为未
     *   七月起于立秋 → 月支为申
     *   八月起于白露 → 月支为酉
     *   九月起于寒露 → 月支为戌
     *   十月起于立冬 → 月支为亥
     *   十一月起于大雪 → 月支为子
     *   十二月起于小寒 → 月支为丑
     * 
     * 若日期早于当年小寒，则视为上一年腊月（丑月），并以“上一年年干”为依据计算天干。
     * 
     * 月干计算规则：
     * 月干需根据年干，按​​五虎遁​​规则推算，如下表：
     * 
     *   年干 → 正月干
     *   甲、己 → 丙
     *   乙、庚 → 戊
     *   丙、辛 → 庚
     *   丁、壬 → 壬
     *   戊、癸 → 甲
     * 
     * 然后从正月干开始顺序循环（每月顺推一次天干）。
     * 
     * 例如：
     * - 若年干为甲，正月天干为丙，那么二月为丁、三月为戊……以此类推。
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getMonthPillar(string|\DateTimeInterface $date): array
    {
        $dt = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        $year = (int)$dt->format('Y');
        $solarTerms = DateConverter::getSolarTermsByYear($year);
        // 获取所有节令的起始时间
        $jieqi_order = [
            '立春',
            '惊蛰',
            '清明',
            '立夏',
            '芒种',
            '小暑',
            '立秋',
            '白露',
            '寒露',
            '立冬',
            '大雪',
            '小寒'
        ];
        $monthBranches = ['寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥', '子', '丑'];
        $monthIndex = null;
        foreach ($jieqi_order as $i => $jieqi) {
            if (!isset($solarTerms[$jieqi])) continue;
            $jieqiDate = new \DateTime($solarTerms[$jieqi]);
            if ($dt >= $jieqiDate) {
                $monthIndex = $i;
            } else {
                break;
            }
        }
        if ($monthIndex === null) {
            // 小寒前属于上一年腊月（丑月）
            $monthIndex = 11;
            $year--; // 月柱归到上一年
        }
        $yearPillar = self::getYearPillar($date); // 获取该年年干
        $yearGan = $yearPillar['tiangan'];
        $monthGanStartMap = [
            '甲' => '丙',
            '己' => '丙',
            '乙' => '戊',
            '庚' => '戊',
            '丙' => '庚',
            '辛' => '庚',
            '丁' => '壬',
            '壬' => '壬',
            '戊' => '甲',
            '癸' => '甲',
        ];

        $heavenlyStems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $startGan = $monthGanStartMap[$yearGan];
        $startIndex = array_search($startGan, $heavenlyStems);

        // 月干是从正月开始依次排列的
        $ganIndex = ($startIndex + $monthIndex) % 10;
        $monthGan = $heavenlyStems[$ganIndex];
        $monthZhi = $monthBranches[$monthIndex];

        return [
            'tiangan' => $monthGan,
            'dizhi' => $monthZhi
        ];
    }

    /**
     * 计算日柱
     * 
     * 根据指定阳历日期计算八字中的“日柱”（天干地支组合）。
     * 日柱是以“日”为单位轮转干支组合，60天为一个周期。
     * 
     * 计算原理如下：
     * 
     * 1. 设置一个参考基准日（1899-12-22），该日为已知的“甲子日”（60甲子第1日）。
     * 2. 将输入日期与该基准日进行天数差计算。
     * 3. 根据天数差对10（天干）和12（地支）取模，得到对应的天干地支。
     * 
     *    日干索引 = (天数差 + 跨日修正) % 10
     *    日支索引 = (天数差 + 跨日修正) % 12
     * 
     * 4. 特别处理：如果当前时间为 23:00~23:59（即子时上半夜），在命理学中被视为“次日开始”，
     *    因此加 1 天的偏移量（offset）以反映跨日现象。
     * 
     * 注意事项：
     * - 公历 1899-12-22 被广泛作为八字推演中“甲子日”的标准起点，精度足够满足现代命理应用。
     * - 本方法不考虑闰秒或其他天文校准误差，适用于日常八字分析。
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getDayPillar(string|\DateTimeInterface $date): array
    {
        $dt = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        $baseDate = new \DateTime('1899-12-22'); // 这是干支日历中一个“甲子日”
        $days = (int)$dt->diff($baseDate)->format('%a');
        $heavenlyStems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $earthlyBranches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

        $hour = (int)$dt->format('H');
        $offset = ($hour == 23) ? 1 : 0;
        return [
            'tiangan' => $heavenlyStems[($days + $offset) % 10],
            'dizhi' => $earthlyBranches[($days + $offset) % 12]
        ];
    }

    /**
     * 计算时柱 
     * 
     * 根据给定阳历时间计算八字中的“时柱”（出生时辰对应的天干地支）。
     * 时柱是八字中最精细的一柱，对应 2 小时为一个时辰，共十二时辰，十二地支循环。
     * 天干则依据“日柱天干 + 地支”通过“五鼠遁”法推算。
     * 
     * 计算原理：
     * 
     * 1. 时区统一：确保使用东八区（北京时间）进行计算。
     * 2. 子时特殊处理：命理学中 23:00~00:59 被视为“子时”，但分“前子”和“后子”：
     *    - 若为 23:00~23:59（晚子时），算作“次日时辰”，需以“次日”日柱来推天干。
     *    - 若为 00:00~00:59（早子时），算作“今日时辰”，不跨日；
     * 3. 地支时辰：根据小时确定所属地支时辰（每2小时一个地支，23:00起算为子）。
     * 4. 时干推算：根据“日柱天干”与“地支”组合，按《五鼠遁》口诀定出时干。
     * 
     * 五鼠遁口诀（时干起始点）：
     * - 甲己日甲子，乙庚日丙子，丙辛日戊子，丁壬日庚子，戊癸日壬子。
     * 以此为起点，时干顺延。
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getHourPillar(string|\DateTimeInterface $date): array
    {
        $dt = $date instanceof \DateTimeInterface ? $date : new \DateTime($date);
        $hour = (int)$dt->format('H');
        $dayPillar = self::getDayPillar($dt);
        $dayGan = $dayPillar['tiangan'];
        $dizhi = self::getHourBranch($hour);
        return [
            'tiangan' => self::calcHourGan($dayGan, $dizhi),
            'dizhi' => $dizhi
        ];
    }

    /**
     * 获取出生日期对应的四柱八字（年柱、月柱、日柱、时柱）
     *
     * 返回按顺序组成的八字数组，每个元素为一个完整的干支组合（如“辛巳”、“壬辰”），
     * 顺序为：年柱、月柱、日柱、时柱。
     *
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return string[] 长度为 4 的八字数组
     */
    public static function getFourPillars(string|\DateTimeInterface $date): array
    {
        $getYearPillar = BaZiCalculator::getYearPillar($date);
        $getMonthPillar = BaZiCalculator::getMonthPillar($date);
        $getDayPillar = BaZiCalculator::getDayPillar($date);
        $getHourPillar = BaZiCalculator::getHourPillar($date);

        return [
            $getYearPillar['tiangan'] . $getYearPillar['dizhi'],
            $getMonthPillar['tiangan'] . $getMonthPillar['dizhi'],
            $getDayPillar['tiangan'] . $getDayPillar['dizhi'],
            $getHourPillar['tiangan'] . $getHourPillar['dizhi'],
        ];
    }


    /**
     * 根据小时获取地支时辰
     * 
     * 地支与时辰对应如下，每两个小时为一个地支时段：
     * - 子（23:00~00:59）
     * - 丑（01:00~02:59）
     * - 寅（03:00~04:59）
     * - 卯（05:00~06:59）
     * - 辰（07:00~08:59）
     * - 巳（09:00~10:59）
     * - 午（11:00~12:59）
     * - 未（13:00~14:59）
     * - 申（15:00~16:59）
     * - 酉（17:00~18:59）
     * - 戌（19:00~20:59）
     * - 亥（21:00~22:59）
     * 
     * 实现逻辑：
     * - 23点特殊处理为子时的起点（index = 0）
     * - 其他时间使用 `(hour + 1) / 2` 向下取整获得时辰段
     * 
     * @param int $hour 小时（0~23）
     * 
     * @return string
     */
    private static function getHourBranch(int $hour): string
    {
        $branches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        // 23点 ~ 0点 属于子时，特殊处理
        $index = ($hour === 23) ? 0 : intdiv($hour + 1, 2);
        return $branches[$index];
    }

    /**
     * 根据日干与时支计算时干（五鼠遁）
     * 
     * 命理学中，时柱的天干（时干）并不独立，而是由当日的日干通过“五鼠遁”法则演化而来。
     * 
     * 口诀为：
     * - 甲己日：甲子起（甲、乙、丙...）
     * - 乙庚日：丙子起
     * - 丙辛日：戊子起
     * - 丁壬日：庚子起
     * - 戊癸日：壬子起
     * 
     * 即，日干与特定的“起始时干”相关联，然后从该时干起依照地支顺序依次推算。
     * 
     * 示例：若日干为“庚”，时支为“午”
     * - 对应起始干为“丙”
     * - “子”起始为“丙”，按地支顺延，“午”为第 6 位，则干为“丙 + 6 = 壬”
     * 
     * @param string $dayGan 日柱天干
     * @param string $dizhi 时柱地支
     * 
     * @return string 
     */
    private static function calcHourGan(string $dayGan, string $dizhi): string
    {
        // 天干和地支顺序
        $tiangan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $dizhiOrder = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        // 五鼠遁规则：甲己日甲子，乙庚日丙子...
        $startGan = match ($dayGan) {
            '甲', '己' => '甲',
            '乙', '庚' => '丙',
            '丙', '辛' => '戊',
            '丁', '壬' => '庚',
            '戊', '癸' => '壬'
        };
        // 计算时干
        $startIdx = array_search($startGan, $tiangan);
        $dizhiIdx = array_search($dizhi, $dizhiOrder);
        return $tiangan[($startIdx + $dizhiIdx) % 10];
    }
}
