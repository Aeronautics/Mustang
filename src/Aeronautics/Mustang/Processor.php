<?php

namespace Aeronautics\Mustang;

use InvalidArgumentException;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Value\ValueList;
use Sabberworm\CSS\Property\Import;
use Sabberworm\CSS\Property\Charset;
use Sabberworm\CSS\CSSList\MediaQuery;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Rule\Rule;

class Processor
{
    public $document;
    public $output;
    public $library;
    public $targetFileName;
    public $lookupFolders = array();
    
    public function __construct($mainFile, array $lookupFolders = array())
    {
        $this->targetFileName = realpath($mainFile);
        $this->lookupFolders = $this->filterFolders($lookupFolders);        
        if (!$this->targetFileName) {
            throw new InvalidArgumentException("Could not find file $mainfile");
        }
        
        if (!$this->lookupFolders) {
            throw new InvalidArgumentException("No valid lookup folders");
        }
    }
    
    protected function createEmptyDocument()
    {
        $output = new Parser('', "utf-8");
        return $output->parse();
    }
    
    protected function createDocument($fileName)
    {
        $parser = new Parser(file_get_contents($fileName), "utf-8");
        return $parser->parse();
    }
    
    public function process()
    {
        $this->document = $this->createDocument($this->targetFileName);
        $this->output = $this->createEmptyDocument();
        $this->library = array();
        foreach($this->document->getContents() as $content) {
            if ($content instanceof Import) {
                $this->importLibrary($content);
            } elseif ($content instanceof DeclarationBlock) {
                $blocks = array();
                foreach ($this->expandBlock($content) as $block) {
                    foreach ($this->processBlock($block) as $b) {
                        $blocks[] = $b;
                    }
                }
                $blocks = $this->compress($blocks);
                foreach ($blocks as $b) {
                    $this->output->append($b);
                }
            }
        }
        print $this->output;
        return $this->output;
    }
    
    protected function compress($blocks)
    {
        $blockRules = array();
        $newBlocks = array();
        $rulesPerSelector = array();
        foreach ($blocks as $content) {
            if ($content instanceof DeclarationBlock) {
                $selector = $this->getSelectorString($content);
                if (!isset($rulesPerSelector[$selector])) {
                    $rulesPerSelector[$selector] = array();
                }
                foreach ($content->getRules() as $rule) {
                    $rulesPerSelector[$selector][] = $rule;
                }
            }
        }
        foreach ($rulesPerSelector as $selector => $rules) {
            $newBlock = new DeclarationBlock;
            $newBlock->setSelector($selector);
            foreach ($rules as $r) {
                $newBlock->addRule($r);
            }
            $newBlocks[] = $newBlock;
        }
        return $newBlocks;
    }
    
    protected function getSelectorString($block)
    {
        $blockSelector = $block->getSelectors();
        return $blockSelector ? $blockSelector[0]->getSelector() : null;
    }
    
    protected function processLibrary($block, $libName, $libValues = array()) 
    {
        $blocks = array();
        $blockSelector = $this->getSelectorString($block);
        foreach ($this->library[$libName] as $libBlock) {
            $libSelector = $this->getSelectorString($libBlock);
            if (false !== strpos($libSelector, '-selector')) {
                $newBlock = clone $libBlock;
                $newBlock->setSelector(
                    str_replace('-selector', $blockSelector, $libSelector)
                );
                $blocks = array_merge(
                    $blocks,
                    $this->processBlock($newBlock, $libValues)
                );
            } elseif ('-all' === $blockSelector) {
                $blocks = array_merge(
                    $blocks,
                    $this->processBlock($libBlock, $libValues)
                );
            } elseif ($libSelector === $blockSelector) {
                $newBlock = clone $libBlock;
                $newBlock->setSelector($blockSelector);
                $blocks = array_merge(
                    $blocks,
                    $this->processBlock($newBlock, $libValues)
                );
            }
        }
        return $blocks;
    }
    
    protected function processBlock($block, $callValues = array())
    {
        $blocks = array();
        $newBlock = clone $block;
        foreach ($newBlock->getRules() as $rule) {
            $libraryPresent = false;
            $ruleName = $rule->getRule();
            $libName = "$ruleName.cssl";
            $libValues = explode(' ', $rule->getValue());
            if (isset($this->library[$libName])) {
                $libraryPresent = true;
                $blocks = array_merge(
                    $this->processLibrary($newBlock, $libName, $libValues),
                    $blocks
                );
            }
            foreach ($libValues as $i => &$value) {
                $libName = "$ruleName/$value.cssl";
                $value = preg_replace_callback(
                    '/-param-(\d+)/',
                    function ($match) use ($callValues) {
                        return isset($callValues[$match[1]]) 
                               && '!skip' !== $callValues[$match[1]]
                             ? $callValues[$match[1]]
                             : null;
                    },
                    $value
                );
                $newBlock->removeRule($rule);
                if ('' === trim($value)) {
                    break;
                }
                $newRule = clone $rule;
                $newRule->setValue(implode(' ', $libValues));
                $newBlock->addRule($newRule);
                if (isset($this->library[$libName])) {
                    $libraryPresent = true;
                    $blocks = array_merge(
                        $this->processLibrary(
                            $newBlock, 
                            $libName, 
                            array_slice($libValues, $i+1)
                        ),
                        $blocks
                    );
                }
            }
            if (!$libraryPresent) {
                $blocks[] = $newBlock;
            }
        }
        return $blocks;
    }
    
    protected function expandBlock($block) 
    {
        $singleSelectorBlocks = array();
        foreach ($block->getSelectors() as $selector) {
            $singleSelectorBlock = clone $block;
            $singleSelectorBlock->setSelector($selector);
            $singleSelectorBlocks[] = $singleSelectorBlock;
        }
        $singleRuleBlocks = array();
        foreach ($singleSelectorBlocks as $singleSelectorBlock) {
            foreach ($singleSelectorBlock->getRules() as $rule) {
                $singleRuleBlock = new DeclarationBlock;
                $singleRuleBlock->setSelector($singleSelectorBlock->getSelector());
                $singleRuleBlock->addRule($rule);
                $singleRuleBlocks[] = $singleRuleBlock;
            }
        }
        return $singleRuleBlocks;
    }
    
    protected function importLibrary($content) 
    {
        $path = trim($content->getLocation()->getUrl(), '"');
        if (isset($this->library[$path])) {
            return $path;
        }
        foreach ($this->lookupFolders as $folder) {
            $folderPath = realpath("$folder/$path");
            if ($folderPath) {
                $libraryDocument = $this->createDocument($folderPath);
                $this->importBlocks($path, $libraryDocument);
                return $path;
            }
        }
    }
    
    protected function importBlocks($path, $document) 
    {
        foreach ($document->getContents() as $libContent) {
            if ($libContent instanceof DeclarationBlock) {
                if (!isset($this->library[$path])) {
                    $this->library[$path] = array();
                }
                $this->library[$path] = array_merge(
                    $this->library[$path],
                    $this->expandBlock($libContent)
                );
            } elseif ($libContent instanceof Import) {
                $this->importLibrary($libContent);
            }
        }
    }
          
    public function __toString()
    {
        $result = (string) $this->process();
        return $result;
    }
    
    protected function filterFolders(array $folders = array())
    {
        return array_filter(array_map(
            function($folder) {
                return realpath($folder);
            },
            $folders
        ));
    }
}
