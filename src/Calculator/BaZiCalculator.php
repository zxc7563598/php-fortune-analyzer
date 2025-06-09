<?php

namespace Hejunjie\FortuneAnalyzer\Calculator;

use DateInterval;
use Hejunjie\FortuneAnalyzer\Converter\BaZiConstants;
use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class BaZiCalculator
{
    /**
     * 判断大运排盘方向（顺推 or 逆推）
     *
     * 根据年柱的“天干”部分，获取阴阳
     * 原则：阳男阴女顺推，阴男阳女逆推
     *
     * @param array $pillars 四柱数组：[年柱, 月柱, 日柱, 时柱]
     * @param int $gender 性别：1=男，0=女
     * 
     * @return bool true=顺排，false=逆排
     * @throws InvalidArgumentException
     */
    public static function isForwardLuck(array $pillars, int $gender): bool
    {
        if (count($pillars) !== 4) {
            throw new \InvalidArgumentException("四柱数组必须包含4个元素（年柱、月柱、日柱、时柱）");
        }
        if (!in_array($gender, [0, 1], true)) {
            throw new \InvalidArgumentException("性别参数必须为 1（男）或 0（女）");
        }
        $yearPillar = $pillars[0];
        $yearGan = mb_substr($yearPillar, 0, 1);
        $yinYang = BaZiConstants::TIANGAN_YINYANG[$yearGan] ?? null;
        if (!$yinYang) {
            throw new \InvalidArgumentException("无效的天干字符：{$yearGan}");
        }
        // 阳男阴女顺，阴男阳女逆
        return ($gender === 1 && $yinYang === '阳') || ($gender === 0 && $yinYang === '阴');
    }

    /**
     * 计算起运信息（年龄 + 日期）
     *
     * 原理：
     * 在八字命理中，大运并非在出生后立即开始，而是根据出生时刻与最近节气之间的时间差，
     * 结合排运方向（顺推或逆推），推算出“几岁开始走第一步大运”。这个起运岁数可以是小数，
     * 表示具体到月甚至具体到天，起运日期也因此可以准确推算。
     *
     * 排运方向判断：
     * - 顺排：从出生时间起，查找“之后的最近一个节令”；
     * - 逆排：从出生时间起，查找“之前的最近一个节令”；
     *
     * 步长换算依据（术年制）：
     * - 命理传统中，认为每 3 天走 1 岁大运，即：1 天 = 1 岁 / 3；
     * - 因此：1 岁 = 3 天 = 3 × 24 × 60 = 4320 分钟；
     * - 起运年龄 = 时间差（分钟） ÷ 4320；
     *
     * 举例：
     * - 出生于 2025-06-05 13:30:00，最近节气为 2025-06-05 19:00:00；
     * - 相差 5.5 小时 ≈ 330 分钟，起运年龄为 330 ÷ 4320 ≈ 0.08 岁；
     * - 起运日期 = 出生日期 + 0.08 岁（换算为天）≈ 2025-06-08。
     * 
     * 说明：
     * 术年制采用每年 360 天（12 月 × 30 天），因此可以直接将起运 年龄 * 360 来计算天数追加推导日期
     *
     * @param string|\DateTimeInterface $birthDatetime 出生时间（阳历）
     * @param int $gender 性别：1=男，0=女
     * 
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public static function calculateStartAge(string|\DateTimeInterface $birthDatetime, int $gender): array
    {
        $birthDatetime = is_string($birthDatetime) ? new \DateTime($birthDatetime) : $birthDatetime;
        $birthTimestamp = $birthDatetime->getTimestamp();
        // 获取四柱
        $pillars = self::getFourPillars($birthDatetime);
        // 判断排运方向
        $isForward = self::isForwardLuck($pillars, $gender);
        // 获取节气
        $birthYear = (int)$birthDatetime->format('Y');
        $prevTerms = DateConverter::getSolarTermsByYear($birthYear - 1, true);
        $currentTerms = DateConverter::getSolarTermsByYear($birthYear, true);
        $nextTerms = DateConverter::getSolarTermsByYear($birthYear + 1, true);
        // 合并并排序节气
        $allTerms = [];
        foreach ($prevTerms as $solar_term => $date) {
            $allTerms[strtotime($date)] = ['year' => ($birthYear - 1), 'solar_term' => $solar_term];
        }
        foreach ($currentTerms as $solar_term => $date) {
            $allTerms[strtotime($date)] = ['year' => ($birthYear), 'solar_term' => $solar_term];
        }
        foreach ($nextTerms as $solar_term => $date) {
            $allTerms[strtotime($date)] = ['year' => ($birthYear + 1), 'solar_term' => $solar_term];
        }
        // 查找参照节气时间
        $refTimestamp = null;
        foreach (array_keys($allTerms) as $termTimestamp) {
            if ($isForward && $termTimestamp > $birthTimestamp) {
                $refTimestamp = $termTimestamp;
                break;
            }
            if (!$isForward && $termTimestamp < $birthTimestamp) {
                $refTimestamp = $termTimestamp;
            }
        }
        if ($refTimestamp === null) {
            throw new \RuntimeException('未找到合适的节气时间点进行计算');
        }
        // 计算分钟差并换算为岁数（1 岁 = 4320 分钟）
        $diffMinutes = abs($birthTimestamp - $refTimestamp) / 60;
        $startAge = round($diffMinutes / 4320, 2);
        $years = floor($startAge);
        $remainingYears = $startAge - $years;
        $months = floor($remainingYears * 12);
        $remainingMonths = ($remainingYears * 12) - $months;
        $days = round($remainingMonths * 30); // 简化按30天/月
        // 计算起运日期（逐步增加年、月、日）
        $startLuckDate = clone $birthDatetime;
        $startLuckDate->add(new DateInterval("P{$years}Y"));
        $startLuckDate->add(new DateInterval("P{$months}M"));
        $startLuckDate->add(new DateInterval("P{$days}D"));
        return [
            'age' => $startAge,
            'date' => $startLuckDate->format('Y-m-d H:i:s')
        ];
    }

    /**
     * 排出大运列表
     *
     * @param int $gender 性别，1=男，0=女
     * @param \DateTimeInterface|string $birthDatetime 出生时间
     * @param int $count 大运排多少步，默认8步
     *
     * @return array 大运列表，每项包含：step(步数), luckPillar(干支), startAge(岁), startDate(日期), wuXing(五行), shiShen(十神)
     */
    public static function getLuckCycles(string|\DateTimeInterface $birthDatetime, int $gender, int $count = 8): array
    {
        // 计算起运年龄和起运日期
        $start = self::calculateStartAge($birthDatetime, $gender);
        $startAge = $start['age'];
        $startDate = new \DateTime($start['date']);
        // 获取四柱
        $pillars = self::getFourPillars($birthDatetime);
        // 计算日柱天干索引，用作大运天干起点
        $startGanIndex = array_search(mb_substr($pillars[2], 0, 1), BaZiConstants::TIANGAN, true);
        if ($startGanIndex === false) {
            throw new \InvalidArgumentException("无效的天干：" . mb_substr($pillars[2], 0, 1));
        }
        // 确定排运方向
        $isForward = self::isForwardLuck($pillars, $gender);
        // 计算大运地支起点（根据起运方向和日柱地支）
        $startZhiIndex = array_search(mb_substr($pillars[2], 1, 1), BaZiConstants::DIZHI, true);
        if ($startZhiIndex === false) {
            throw new \InvalidArgumentException("无效的地支：" . mb_substr($pillars[2], 1, 1));
        }
        // 从起运起，循环计算每步大运干支和起始年龄、时间
        $luckCycles = [];
        for ($i = 0; $i < $count; $i++) {
            $step = $i + 1;
            // 天干索引顺逆推
            $ganIndex = ($isForward)
                ? ($startGanIndex + $i) % 10
                : ($startGanIndex + 10 - $i) % 10;
            // 地支索引顺逆推
            $zhiIndex = ($isForward)
                ? ($startZhiIndex + $i) % 12
                : ($startZhiIndex + 12 - $i) % 12;
            $luckPillar = BaZiConstants::TIANGAN[$ganIndex % 10] . BaZiConstants::DIZHI[$zhiIndex % 12];
            $luckAge = $startAge + $i * 10;
            $luckDate = (clone $startDate)->modify("+" . ($i * 10) . " years");
            $luckCycles[] = [
                'step' => $step,
                'luckPillar' => $luckPillar,
                'startAge' => round($luckAge, 2),
                'startDate' => $luckDate->format('Y-m-d H:i:s'),
                "wuXing" => [
                    'tiangan' => BaZiConstants::TIANGAN_WUXING[mb_substr($luckPillar, 0, 1)],
                    'dizhi' => BaZiConstants::DIZHI_WUXING[mb_substr($luckPillar, 1, 1)],
                ],
                "shiShen" => [
                    "tiangan" => self::getShiShen(mb_substr($pillars[2], 0, 1), mb_substr($luckPillar, 0, 1)),
                    "dizhi" => self::getShiShen(mb_substr($pillars[2], 0, 1), mb_substr($luckPillar, 1, 1)),
                ]
            ];
        }
        return $luckCycles;
    }

    /**
     * 获取指定干对日主的十神（以日干为基准计算十神）
     *
     * 十神是根据日主（出生日的天干）与其它天干（或地支主气藏干）之间的
     * 五行生克关系与阴阳属性来推演得出，用于分析命理关系。
     *
     * 【计算方法说明】：
     * 1. 若 $target 是天干（如“乙”），直接参与计算；
     * 2. 若 $target 是地支（如“辰”），则提取其主气（第一个藏干）参与计算；
     * 3. 找出日干与目标天干的五行（如“丙” -> 火，“乙” -> 木）；
     * 4. 判断五行之间的关系：
     *    - 同五行     => “同我”组：比肩、劫财
     *    - 生我       => “印”组：正印、偏印
     *    - 我生       => “食伤”组：食神、伤官
     *    - 克我       => “官杀”组：正官、七杀
     *    - 我克       => “财”组：正财、偏财
     * 5. 结合日干与目标干的阴阳（如丙为阳，丁为阴）：
     *    - 阴阳相同 => 偏系（如偏印、劫财）
     *    - 阴阳相异 => 正系（如正印、比肩）
     *
     * 最终组合如：
     *   - “生我 + 同性” => 偏印
     *   - “克我 + 异性” => 正官
     *   - “我生 + 异性” => 食神
     *   - “同我 + 异性” => 劫财
     *   - 等等……
     *
     * @param string $dayGan 日主天干（如“丙”）
     * @param string $target 另一个天干或地支（如“乙”或“辰”）
     *
     * @return string 十神名称（如“劫财”、“正财”、“伤官”等），若无法识别返回空字符串
     */
    public static function getShiShen(string $dayGan, string $target): string
    {
        $targetGan = '';
        if (isset(BaZiConstants::TIANGAN_WUXING[$target])) {
            $targetGan = $target;
        }
        if (!$targetGan) {
            if (isset(BaZiConstants::CANG_GAN_MAP[$target])) {
                $targetGan = BaZiConstants::CANG_GAN_MAP[$target][0] ?? null; // 使用主气
            }
        }
        if ($targetGan) {
            $me = BaZiConstants::TIANGAN_WUXING[$dayGan];
            $he = BaZiConstants::TIANGAN_WUXING[$targetGan];
            $meYinYang = BaZiConstants::TIANGAN_YINYANG[$dayGan];
            $heYinYang = BaZiConstants::TIANGAN_YINYANG[$targetGan];
            foreach (BaZiConstants::WUXING_RELATION_MAP as $relation => $map) {
                if (($map[$me] ?? null) === $he) {
                    $key = $relation . '-' . ($meYinYang === $heYinYang ? '同性' : '异性');
                    return BaZiConstants::SHI_SHEN_MAP[$key] ?? '';
                }
            }
            if ($me === $he) {
                $key = '同我-' . ($meYinYang === $heYinYang ? '同性' : '异性');
                return BaZiConstants::SHI_SHEN_MAP[$key] ?? '';
            }
        }
        return '';
    }

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
