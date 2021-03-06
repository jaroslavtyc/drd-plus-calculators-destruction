<?php declare(strict_types=1);

namespace DrdPlus\CalculatorSkeleton;

use DrdPlus\CalculatorSkeleton\Web\CalculatorWebPartsContainer;
use DrdPlus\RulesSkeleton\Cache;
use DrdPlus\RulesSkeleton\HtmlHelper;
use DrdPlus\RulesSkeleton\Request;
use DrdPlus\RulesSkeleton\ServicesContainer;
use DrdPlus\RulesSkeleton\Web\RulesMainContent;
use DrdPlus\RulesSkeleton\Web\WebPartsContainer;
use Granam\Git\Git;
use Granam\String\StringTools;

/**
 * @method CalculatorConfiguration getConfiguration()
 */
class CalculatorServicesContainer extends ServicesContainer
{
    /** @var CalculatorRequest */
    private $calculatorRequest;

    /** @var Memory */
    private $memory;

    /** @var DateTimeProvider */
    private $dateTimeProvider;

    /** @var CurrentValues */
    private $currentValues;

    /** @var History */
    private $history;

    /** @var CalculatorContent */
    private $calculatorContent;

    /** @var RulesMainContent */
    private $calculatorWebContent;

    /** @var CalculatorWebPartsContainer */
    private $calculatorRoutedWebPartsContainer;

    /** @var CalculatorWebPartsContainer */
    private $calculatorRootWebPartsContainer;

    /** @var GitReader */
    private $gitReader;

    public function __construct(CalculatorConfiguration $calculatorConfiguration, HtmlHelper $htmlHelper)
    {
        parent::__construct($calculatorConfiguration, $htmlHelper);
    }

    /**
     * @return Request|CalculatorRequest
     */
    public function getRequest(): Request
    {
        if ($this->calculatorRequest === null) {
            $this->calculatorRequest = CalculatorRequest::createFromGlobals($this->getBotParser(), $this->getEnvironment());
        }
        return $this->calculatorRequest;
    }

    public function getMemory(): Memory
    {
        if ($this->memory === null) {
            $this->memory = new Memory(
                $this->getMemoryStorage(),
                $this->getDateTimeProvider(),
                $this->getConfiguration()->getCookiesTtl()
            );
        }
        return $this->memory;
    }

    protected function getMemoryStorage(): CookiesStorage
    {
        return new CookiesStorage($this->getCookiesService(), $this->getCookiesStorageKeyPrefix() . '-memory');
    }

    public function getDateTimeProvider(): DateTimeProvider
    {
        if ($this->dateTimeProvider === null) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $this->dateTimeProvider = new DateTimeProvider(new \DateTimeImmutable());
        }
        return $this->dateTimeProvider;
    }

    protected function getCookiesStorageKeyPrefix(): string
    {
        return StringTools::getClassBaseName(static::class) . '-' . $this->getConfiguration()->getCookiesPostfix();
    }

    public function getCurrentValues(): CurrentValues
    {
        if ($this->currentValues === null) {
            $this->currentValues = new CurrentValues($this->getRequest()->getValuesFromGet(), $this->getMemory());
        }
        return $this->currentValues;
    }

    public function getHistory(): History
    {
        if ($this->history === null) {
            $this->history = new History(
                $this->getHistoryStorage(),
                $this->getDateTimeProvider(),
                $this->getConfiguration()->getCookiesTtl()
            );
        }
        return $this->history;
    }

    protected function getHistoryStorage(): CookiesStorage
    {
        return new CookiesStorage($this->getCookiesService(), $this->getCookiesStorageKeyPrefix() . '-history');
    }

    public function getCalculatorContent(): CalculatorContent
    {
        if ($this->calculatorContent === null) {
            $this->calculatorContent = new CalculatorContent(
                $this->getRulesMainContent(),
                $this->getMenu(),
                $this->getCurrentWebVersion(),
                $this->getPassedWebCache()
            );
        }
        return $this->calculatorContent;
    }

    public function getRulesMainContent(): RulesMainContent
    {
        if ($this->calculatorWebContent === null) {
            $this->calculatorWebContent = new RulesMainContent(
                $this->getHtmlHelper(),
                $this->getHead(),
                $this->getRoutedWebPartsContainer()->getRulesMainBody()
            );
        }
        return $this->calculatorWebContent;
    }

    public function getRoutedWebPartsContainer(): \DrdPlus\RulesSkeleton\Web\WebPartsContainer
    {
        if ($this->calculatorRoutedWebPartsContainer === null) {
            $this->calculatorRoutedWebPartsContainer = new CalculatorWebPartsContainer(
                $this->getPass(),
                $this->getRoutedWebFiles(),
                $this->getDirs(),
                $this->getHtmlHelper(),
                $this->getRequest()
            );
        }
        return $this->calculatorRoutedWebPartsContainer;
    }

    public function getRootWebPartsContainer(): WebPartsContainer
    {
        if ($this->calculatorRootWebPartsContainer === null) {
            $this->calculatorRootWebPartsContainer = new CalculatorWebPartsContainer(
                $this->getPass(),
                $this->getRootWebFiles(),
                $this->getDirs(),
                $this->getHtmlHelper(),
                $this->getRequest()
            );
        }
        return $this->calculatorRootWebPartsContainer;
    }

    public function getGit(): Git
    {
        if ($this->gitReader === null) {
            $this->gitReader = new GitReader();
        }
        return $this->gitReader;
    }

    public function getTablesWebCache(): Cache
    {
        return $this->getDummyWebCache();
    }

    public function getPassWebCache(): Cache
    {
        return $this->getDummyWebCache();
    }

    public function getPassedWebCache(): Cache
    {
        return $this->getDummyWebCache();
    }

}