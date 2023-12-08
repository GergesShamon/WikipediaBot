<?php
namespace Bot\Tasks;

use RuntimeException;
use WikiConnect\MediawikiApi\Client\Action\Exception\UsageException;
use WikiConnect\MediawikiApi\DataModel\Page;
use WikiConnect\MediawikiApi\DataModel\Content;
use WikiConnect\MediawikiApi\DataModel\Revision;
use WikiConnect\MediawikiApi\DataModel\EditInfo;
use Bot\IO\Util;
use Bot\IO\Logger;

class FeaturedContent extends Task
{
    private function getPage($name): Page {
        return $this->services->newPageGetter()->getFromTitle($name);
    }
    private function getTagType($text): string {
        if (preg_match("/نوع الترشيح =(.*)/u", $text, $matches)) {
            Logger::info("FeaturedContent: Tag type " .trim($matches[1]));
            return trim($matches[1]);
        } else {
            throw new RuntimeException("Error: The tag type is not known.");
        }
    }
    private function getComment($text): string {
        if (preg_match("/<!-- اكتب رسالتك أسفل هذا السطر -->(.*?)<!-- اكتب رسالتك أعلى هذا السطر -->/s", $text, $matches)) {
            return trim($matches[1]);
        } else {
            throw new RuntimeException("Error: The comment is not known.");
        }
    }
    private function getEnd($text): string {
        if (preg_match("/تاريخ انتهاء المراجعة =(.*)/u", $text, $matches)) {
            return trim($matches[1]);
        } else {
            throw new RuntimeException("Error: The Review end date is not known.");
        }
    }
    private function getTemplateTag($tag): string {
        switch ($tag) {
            case "مختارة":
                return "قالب:ترشيح مقخ";
            case "جيدة":
                return "قالب:ترشيح مقج";
            case "قائمة":
                return "قالب:ترشيح قم";
            default:
                throw new RuntimeException("Error: Tag type is wrong.");
        }
    }
    private function getDataPage($name): array {
        $page = $this->getPage($name);
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $options = [
            "Name" => str_replace(" ", "_", $name)
        ];
        
        $CountSections = count($services->newParser()->parsePage($page->getPageIdentifier())["sections"]);
        
        // remove cat links
        $text = preg_replace("/\[\[[^:]*?\]\]/", "", $text);
        // remove tables
        $text = preg_replace("/{\|[\s\S]*?\|}/", "", $text);
        // remove template
        $text = preg_replace("/\{\{[\s\S]*?\}\}/", "", $text);
        // remove html tag include ref tags
        $text = preg_replace("/<[^>]+>/", "", $text);
        // remove all comments
        $text = preg_replace("/<!--.*?-->/", "", $text);
        // remove all external links
        $text = preg_replace("/\[http[^\]]+\]/", "", $text);
        // replace all wikilinks to be like [from|some text ] to from
        $text = preg_replace_callback(
            "/\[\[(.*?)\|(.*?)\]\]/",
            function ($matches) {
                return $matches[1];
            },
            $text
        );
        // remove tables like this "{| |}"
        $text = preg_replace("/{\|[\s\S]*?\|}/", "", $text);
        
        $query1 = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/FeaturedContent1.sql", $options))[0];
        $query2 = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/FeaturedContent2.sql", $options))[0];
        $query3 = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/FeaturedContent3.sql", $options))[0];
        $query4 = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/FeaturedContent4.sql", $options))[0];
        $query5 = $this->query->getArray(Util::ReadFile(FOLDER_SQL . "/FeaturedContent5.sql", $options))[0];
        $query6 = [
            "sections" => $CountSections,
            "words" => count(preg_split("/\w+/u", $text, -1, PREG_SPLIT_NO_EMPTY)),
        ];
        return array_merge($query1, $query2, $query3, $query4, $query5, $query6);
    }
    private function getVotingPageIndex($tag): string {
        switch ($tag) {
            case "مختارة":
                return "ويكيبيديا:ترشيحات المقالات المختارة";
            case "جيدة":
                return "ويكيبيديا:ترشيحات المقالات الجيدة";
            case "قائمة":
                return "ويكيبيديا:ترشيحات القوائم المختارة";
            default:
                throw new RuntimeException("Error: Tag type is wrong.");
        }
    }
    private function CreateVotePage(
        $data,
        $name,
        $comment,
        $TemplateTag,
        $tag
   ) {
        $VotingPageName = $this->getVotingPageIndex($tag) . "/" . $name;
        $VotePage = $this->getPage($VotingPageName);
        $TextPage = Util::PregReplace(
            $VotePage->getRevisions()->getLatest()->getContent()->getData(),
            [
                ["/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الكلمات\}\}/u", $data["words"]],
                ["/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الأقسام\}\}/u", $data["sections"]],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الزرقاء\}\}/u",
                    $data["blue_links"],
                ],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الحمراء\}\}/u",
                    $data["red_links"],
                ],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|توضيح\}\}/u",
                    $data["disambiguation_links"],
                ],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|التحويلات\}\}/u",
                    $data["redirects"],
                ],
                ["/{\{\بيانات مقالة مرشحة\/مدخل آلي\|التعديلات\}\}/u", $data["edits"]],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الوصلات إلى الصفحة\}\}/u",
                    $data["to_links"],
                ],
                [
                    "/{\{\بيانات مقالة مرشحة\/مدخل آلي\|الوصلات من الصفحة\}\}/u",
                    $data["blue_links"] + $data["red_links"],
                ],
                [
                    "/{\{\وضع المراجعة\}\}/u",
                    "1",
                ],
                ["/<includeonly>/", ""],
                ["/<\/includeonly>/", ""],
                ["/{{{عنوان}}}/", $name],
                ["/{{{تعليق}}}/", $comment],
            ],
        );
        $revision = new Revision(new Content($TextPage),$VotePage->getPageIdentifier());
        $this->services->newRevisionSaver()->save($revision, new EditInfo("بوت: إنشاء."));
    }
    private function getPages() {
        return CoreSystem::QueryDB(
            CoreSystem::ReadFile(__DIR__ . "/../sql/GetPagesFromCategories.sql", [
                "Name" => "تتبع_صفحات_مراجعة_مقبولة",
            ]),
        );
    }
    private function newCommunityMssages($name, $tag): void {
        $page = $this->getPage("ويكيبيديا:رسائل للمجتمع");
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $revision = new Revision(new Content($text. "\n# مقالة '''[[".$name."]]''' مرشحة لنيل وسم ال".$tag."، يمكنم الإدلاء بآرائكم والتصويت بما ترونه مناسباً في '''[[ويكيبيديا:ترشيحات المقالات ال".$tag."/".$name."|هذه الصفحة]]'''. --~~~~"),$page->getPageIdentifier());
        $this->services->newRevisionSaver()->save($revision);
    }
    private function ReviewPageFormat($name) {
        $page = $this->getPage("ويكيبيديا:مراجعة الزملاء/$name");
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $text = str_replace("مرشحة = لا", "مرشحة = نعم", $text);
        $text = str_replace("مرشحة =لا", "مرشحة =نعم", $text);
        $revision = new Revision(new Content($text),$page->getPageIdentifier());
        $this->services->newRevisionSaver()->save($revision);
    }
    
    private function AddToPageVotes($name, $tag): void {
        switch ($tag) {
            case "مختارة":
                $template = "مممخ";
            case "جيدة":
                $template = "مممج";
            case "قائمة":
                $template = "قمخ";
            default:
                throw new RuntimeException("Error: Tag type is wrong.");
        }
        $page = $this->getPage($this->getVotingPageIndex($tag));
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $revision = new Revision(new Content(str_replace(
                        "اسم المقالة}} -->",
                        "اسم المقالة}} -->\n{{".$template."|".$name."}}",
                        $text
                    )),$page->getPageIdentifier());
        $this->services->newRevisionSaver()->save($revision, new EditInfo("بوت: إضافة ترشيح جديد."));
    }
    private function AddTagVote($name, $tag): void {
        switch ($tag) {
            case "مختارة":
                $template = "ترشيح مقالة مختارة";
            case "جيدة":
                $template = "ترشيح مقالة جيدة";
            case "قائمة":
                $template = "ترشيح قائمة مختارة";
            default:
                throw new RuntimeException("Error: Tag type is wrong.");
        }
        $page = $this->getPage($name);
        $text = $page->getRevisions()->getLatest()->getContent()->getData();
        $revision = new Revision(new Content(preg_replace(
                    "/\{\{مراجعة الزملاء\|(.*?)\}\}/",
                    "{{".$template."|{{نسخ:يوم وشهر وسنة}}}}",
                    $text
                )),$page->getPageIdentifier());
        $this->services->newRevisionSaver()->save($revision, new EditInfo("بوت: تغير قالب مراجعة الزملاء إلى ترشيح مقالة"));
    }
    private function TwoDaysPassed($ReviewEndDate) {
        $months = array(
            "يناير" => "January",
            "فبراير" => "February",
            "مارس" => "March",
            "أبريل" => "April",
            "مايو" => "May",
            "يونيو" => "June",
            "يوليو" => "July",
            "أغسطس" => "August",
            "سبتمبر" => "September",
            "أكتوبر" => "October",
            "نوفمبر" => "November",
            "ديسمبر" => "December"
        );
        $targetDate = strtotime(str_replace(array_keys($months), array_values($months), $ReviewEndDate));
        $currentDate = time();
        $dayDifference = round(($currentDate - $targetDate) / (60 * 60 * 24));
        if ($dayDifference >= 2) {
            return true;
        } else {
            return false;
        }
    }
    private function init() {
        $PagesArray = $this->getPages();
        foreach ($PagesArray as $name) {
            if (strpos($name["page_title"], "مراجعة_الزملاء/") !== false) {
                $NamePage = str_replace(
                    "_",
                    " ",
                    str_replace("مراجعة_الزملاء/", "", $name["page_title"]),
                );
                $Page = $this->getPage("ويكيبيديا:مراجعة الزملاء/${name}");
                $TextPage = $Page->getRevisions()->getLatest()->getContent()->getData();
                $_data = $this->getDataPage($NamePage);
                $Comment = $this->getComment($TextPage);
                $Tag = $this->getTagType($TextPage);
                $ReviewEndDate = $this->getReviewEndDate($TextPage);
                if($this->TwoDaysPassed($ReviewEndDate)){
                $TemplateTag = $this->getTemplateTag($Tag);
                $this->CreateVotePage($_data, $NamePage, $Comment, $TemplateTag, $Tag);
                $this->newCommunityMssages($NamePage, $Tag);
                $this->ReviewPageFormat($NamePage);
                $this->AddTagVote($NamePage, $Tag);
                $this->AddToPageVotes($NamePage, $Tag);
                $Archive = new PeerReviewArchive($this->API);
                $Archive->Archive($NamePage);
                } else {
                echo "A page ${NamePage} that is not older than two days.\n";
                }
            }
        }
    }
    private function RUN(): void {
        try {
            $this->init();
            Logger::info("Task FeaturedContent succeeded to execute.");
        } catch (Exception $error) {
            Logger::fatal("Task FeaturedContent failed to execute.", [$error->getMessage()]);
        } catch (ImagickException $error) {
            Logger::fatal("Task FeaturedContent failed to execute.", [$error->getMessage()]);
        } catch (UsageException $error) {
            Logger::fatal("Task FeaturedContent failed to execute.", $error->getApiResult());
        }
    }
}
?>