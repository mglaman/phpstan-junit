<?php declare(strict_types=1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\File\RelativePathHelper;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Style\OutputStyle;

class JUnitErrorFormatter implements ErrorFormatter
{
    /** @var RelativePathHelper */
    private $relativePathHelper;

    public function __construct(RelativePathHelper $relativePathHelper)
    {
        $this->relativePathHelper = $relativePathHelper;
    }

    public function formatErrors(AnalysisResult $analysisResult, OutputStyle $style): int
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $testsuites = $dom->appendChild($dom->createElement('testsuites'));
        /** @var \DomElement $testsuite */
        $testsuite = $testsuites->appendChild($dom->createElement('testsuite'));
        $testsuite->setAttribute('name', 'PHPStan');
        $testsuite->setAttribute('failures', (string) $analysisResult->getTotalErrorsCount());

        $returnCode = 1;

        if (!$analysisResult->hasErrors()) {
            $testcase = $dom->createElement('testcase');
            $testsuite->appendChild($testcase);
            $testsuite->setAttribute('tests', '1');
            $testsuite->setAttribute('failures', '0');

            $returnCode = 0;
        } else {
            /** @var \PHPStan\Analyser\Error[][] $fileErrors */
            $fileErrors = [];
            foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
                if (!isset($fileErrors[$fileSpecificError->getFile()])) {
                    $fileErrors[$fileSpecificError->getFile()] = [];
                }

                $fileErrors[$fileSpecificError->getFile()][] = $fileSpecificError;
            }

            foreach ($fileErrors as $file => $errors) {
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', $this->relativePathHelper->getRelativePath($file));
                $testcase->setAttribute('failures', (string)count($errors));
                $testcase->setAttribute('errors', '0');
                $testcase->setAttribute('tests', (string)count($errors));

                foreach ($errors as $error) {
                    $failure = $dom->createElement('failure');
                    $failure->setAttribute('type', 'error');
                    $failure->setAttribute('message', sprintf('%s on line %d', $error->getMessage(), $error->getLine()));
                    $testcase->appendChild($failure);
                }

                $testsuite->appendChild($testcase);
            }

            $genericErrors = $analysisResult->getNotFileSpecificErrors();
            if (!empty($genericErrors)) {
                $testcase = $dom->createElement('testcase');
                $testcase->setAttribute('name', 'Generic Failures');
                $testcase->setAttribute('failures', (string)count($genericErrors));
                $testcase->setAttribute('errors', '0');
                $testcase->setAttribute('tests', (string)count($genericErrors));
                foreach ($genericErrors as $genericError) {
                    $failure = $dom->createElement('failure');
                    $failure->setAttribute('type', 'error');
                    $failure->setAttribute('message', $genericError);
                    $testcase->appendChild($failure);
                }

                $testsuite->appendChild($testcase);
            }
        }

        $dom->formatOutput = true;

        $style->write($style->isDecorated() ? OutputFormatter::escape($dom->saveXML()) : $dom->saveXML());

        return $returnCode;
    }

}
