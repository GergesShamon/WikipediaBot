<?php
namespace Bot\Tasks;

use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use Bot\IO\Util;
use Bot\IO\ReduceImage;

use Exception;


class GrammarlyChecker extends Task
{

    
    public function RUN() : void {
        try {
            $this->log->error("hh");
            
            $this->log->info("Task GrammarlyChecker succeeded to execute.");
        } catch (Exception $error) {
            $this->log->debug("Task GrammarlyChecker failed to execute.", [$error->getMessage()]);
        } catch (ImagickException $error) {
            $this->log->debug("Task GrammarlyChecker failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            $this->log->debug("Task GrammarlyChecker failed to execute.", $error->getApiResult());
        }
    }
}