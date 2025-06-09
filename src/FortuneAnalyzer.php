<?php

namespace Hejunjie\FortuneAnalyzer;

use Hejunjie\FortuneAnalyzer\Analysis\ShiShenAnalyzer;
use Hejunjie\FortuneAnalyzer\Calculator\BaZiCalculator;
use Hejunjie\FortuneAnalyzer\Calculator\WuXingCalculator;
use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class FortuneAnalyzer
{
    /**
     * 获取八字（四柱：年、月、日、时）
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return string[] 长度为 4 的八字数组
     */
    public static function analyzeFourPillars(string $datetime): array
    {
        return BaZiCalculator::getFourPillars($datetime);
    }

    /**
     * 获取年柱的详细信息（天干地支）
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getYearPillar(string $datetime): array
    {
        return BaZiCalculator::getYearPillar($datetime);
    }

    /**
     * 获取月柱的详细信息（天干地支）
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getMonthPillar(string $datetime): array
    {
        return BaZiCalculator::getMonthPillar($datetime);
    }

    /**
     * 获取日柱的详细信息（天干地支）
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getDayPillar(string $datetime): array
    {
        return BaZiCalculator::getDayPillar($datetime);
    }

    /**
     * 获取时柱的详细信息（天干地支）
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05 13:30:00" 或 DateTime 实例
     * 
     * @return array 
     */
    public static function getHourPillar(string $datetime): array
    {
        return BaZiCalculator::getHourPillar($datetime);
    }

    /**
     * 获取五行统计信息（不含藏干）
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function analyzeWuXingSimple(array $pillars): array
    {
        return WuXingCalculator::getWuXingWithoutHidden($pillars);
    }

    /**
     * 获取五行统计信息（含藏干）
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function analyzeWuXingFull(array $pillars): array
    {
        return WuXingCalculator::getWuXingWithHidden($pillars);
    }

    /**
     * 获取每柱五行元素详情（天干地支和藏干对应五行）
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function getWuXingBreakdown(array $pillars): array
    {
        return WuXingCalculator::getPillarDetailsSimple($pillars);
    }

    /**
     * 识别命盘五行局（如三会局、三合局等）
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function detectWuXingJu(array $pillars): ?array
    {
        return WuXingCalculator::detectJu($pillars);
    }

    /**
     * 阳历转农历
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05" 或 DateTime 实例
     * 
     * @return string 
     */
    public static function convertSolarToLunar(string $date): string
    {
        return DateConverter::getLunarFromSolar($date);
    }

    /**
     * 农历转阳历
     * 
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05" 或 DateTime 实例
     * 
     * @return string 
     */
    public static function convertLunarToSolar(string $date): string
    {
        return DateConverter::getSolarFromLunar($date);
    }

    /**
     * 获取指定年份的 24 节气时间（秒级精度）
     * 
     * @param string|int $year 年份,阳历日期
     * 
     * @return array [['节气名称'=>'日期'],...]
     */
    public static function getSolarTerms(int|string $year): array
    {
        return DateConverter::getSolarTermsByYear($year);
    }

    /**
     * 获取十神分布
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function getShiShenDistribution(array $pillars): array
    {
        return ShiShenAnalyzer::getShiShenDistribution($pillars);
    }

    /**
     * 解析四柱八字中的十神分布
     * 
     * @param array $pillars 四柱数组，格式为 [年柱, 月柱, 日柱, 时柱]，可通过 FortuneAnalyzer::analyzeFourPillars($date) 获取
     * 
     * @return array 
     */
    public static function interpretShiShen(array $pillars): array
    {
        return ShiShenAnalyzer::interpretShiShen($pillars);
    }

    /**
     * 计算起运信息（年龄 + 日期）
     * 
     * @param string|\DateTimeInterface $birthDatetime 出生时间（阳历）
     * @param int $gender 性别：1=男，0=女
     * 
     * @return array
     */
    public static function calculateStartAge(string|\DateTimeInterface $birthDatetime, int $gender): array
    {
        return BaZiCalculator::calculateStartAge($birthDatetime, $gender);
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
        return BaZiCalculator::getLuckCycles($birthDatetime, $gender, $count);
    }
}
