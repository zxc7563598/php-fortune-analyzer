<?php

namespace Hejunjie\FortuneAnalyzer\Analysis;

use DateInterval;
use Hejunjie\FortuneAnalyzer\Calculator\BaZiCalculator;
use Hejunjie\FortuneAnalyzer\Converter\BaZiConstants;
use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class DaYunAnalyzer
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
        $pillars = BaZiCalculator::getFourPillars($birthDatetime);
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
        $pillars = BaZiCalculator::getFourPillars($birthDatetime);
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
            $tiangan = mb_substr($luckPillar, 0, 1);
            $dizhi = mb_substr($luckPillar, 1, 1);
            $hiddenWuxing = [];
            $hidden = [];
            foreach (BaZiConstants::CANG_GAN_MAP[$dizhi] ?? [] as $gan) {
                $hidden[] = $gan;
                $hiddenWuxing[] = BaZiConstants::TIANGAN_WUXING[$gan] ?? null;
            }
            $luckCycles[] = [
                'step' => $step,
                'luckPillar' => $luckPillar,
                'startAge' => round($luckAge, 2),
                'startDate' => $luckDate->format('Y-m-d H:i:s'),
                'cangGan' => implode(',', $hidden),
                "wuXing" => [
                    'tiangan' => BaZiConstants::TIANGAN_WUXING[$tiangan],
                    'dizhi' => BaZiConstants::DIZHI_WUXING[$dizhi],
                    'canggan' => implode(',', $hiddenWuxing)
                ],
                "shiShen" => [
                    "tiangan" => BaZiCalculator::getShiShen(mb_substr($pillars[2], 0, 1), $tiangan, false),
                    "dizhi" => BaZiCalculator::getShiShen(mb_substr($pillars[2], 0, 1), $dizhi, false),
                ]
            ];
        }
        return $luckCycles;
    }
}
