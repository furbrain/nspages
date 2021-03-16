package nspages;

import org.junit.Test;
import org.openqa.selenium.By;
import org.openqa.selenium.WebElement;

import java.util.ArrayList;
import java.util.List;

import static org.junit.Assert.assertEquals;

public class Test_includeItemsInTOC extends Helper {
    @Test
    public void withoutOption(){
        String ns = "ordre_alphabetique_ns";
        generatePage(ns + ":start", addTitlesInOrderToHaveAToc("<nspages -subns>"));

        // Assert links (or their surrounding span) do not have any html id
        List<InternalLink> expectedLinks = new ArrayList<>();
        expectedLinks.add(new InternalLink(ns + ":a:start", "a", ""));
        expectedLinks.add(new InternalLink(ns + ":aa:start", "aa", ""));
        expectedLinks.add(new InternalLink(ns + ":b:start", "b", ""));
        expectedLinks.add(new InternalLink(ns + ":start", "start", ""));
        assertSameLinks(expectedLinks);

        // Assert the TOC only has links for the normal headers
        List<TOCLink> expectedTOCLinks = new ArrayList<>();
        expectedTOCLinks.add(new TOCLink(1, getDriver().getCurrentUrl() + "#a", "A"));
        expectedTOCLinks.add(new TOCLink(1, getDriver().getCurrentUrl() + "#b", "B"));
        expectedTOCLinks.add(new TOCLink(1 ,getDriver().getCurrentUrl() + "#c", "C"));
        assertSameTOC(expectedTOCLinks);

    }

    @Test
    public void withOption(){
        String ns = "ordre_alphabetique_ns";
        generatePage(ns + ":start", addTitlesInOrderToHaveAToc("<nspages -subns -includeItemsInTOC>"));

        // Assert links (or their surrounding span) have the expected html id
        List<InternalLink> expectedLinks = new ArrayList<>();
        expectedLinks.add(new InternalLink(ns + ":a:start", "a", "nspages_" + ns + "astart"));
        expectedLinks.add(new InternalLink(ns + ":aa:start", "aa", "nspages_" + ns + "aastart"));
        expectedLinks.add(new InternalLink(ns + ":b:start", "b", "nspages_" + ns + "bstart"));
        expectedLinks.add(new InternalLink(ns + ":start", "start", "nspages_" + ns + "start"));
        assertSameLinks(expectedLinks);

        // Assert the TOC has the expected links
        List<TOCLink> expectedTOCLinks = new ArrayList<>();
        expectedTOCLinks.add(new TOCLink(1, getDriver().getCurrentUrl() + "#a", "A"));
        expectedTOCLinks.add(new TOCLink(1, getDriver().getCurrentUrl() + "#b", "B"));
        expectedTOCLinks.add(new TOCLink(1 ,getDriver().getCurrentUrl() + "#c", "C"));
        expectedTOCLinks.add(new TOCLink(2, getDriver().getCurrentUrl() + "#nspages_" + ns + "astart", "a"));
        expectedTOCLinks.add(new TOCLink(2, getDriver().getCurrentUrl() + "#nspages_" + ns + "aastart", "aa"));
        expectedTOCLinks.add(new TOCLink(2 ,getDriver().getCurrentUrl() + "#nspages_" + ns + "bstart", "b"));
        expectedTOCLinks.add(new TOCLink(2 ,getDriver().getCurrentUrl() + "#nspages_" + ns + "start", "start"));
        assertSameTOC(expectedTOCLinks);
    }

    //TODO: test with the "usePictures" printer
    //TODO: test TOC indentation after a h2 last header
    //TODO: test with the tree printer that the sublevels indentation are respected in TOC

    @Test
    public void withTwoNspagesTagsIdsAreStillUnique(){
        String ns = "ordre_alphabetique_ns";
        generatePage(ns + ":start", addTitlesInOrderToHaveAToc("<nspages -subns -includeItemsInTOC><nspages -subns -includeItemsInTOC>"));

        // Assert links (or their surrounding span) have the expected html id
        List<InternalLink> expectedLinks = new ArrayList<>();
        //  Links for the 1st tag
        expectedLinks.add(new InternalLink(ns + ":a:start", "a", "nspages_" + ns + "astart"));
        expectedLinks.add(new InternalLink(ns + ":aa:start", "aa", "nspages_" + ns + "aastart"));
        expectedLinks.add(new InternalLink(ns + ":b:start", "b", "nspages_" + ns + "bstart"));
        expectedLinks.add(new InternalLink(ns + ":start", "start", "nspages_" + ns + "start"));
        //  Links for the 2nd tag: same links but with unique ids
        expectedLinks.add(new InternalLink(ns + ":a:start", "a", "nspages_" + ns + "astart1"));
        expectedLinks.add(new InternalLink(ns + ":aa:start", "aa", "nspages_" + ns + "aastart1"));
        expectedLinks.add(new InternalLink(ns + ":b:start", "b", "nspages_" + ns + "bstart1"));
        expectedLinks.add(new InternalLink(ns + ":start", "start", "nspages_" + ns + "start1"));
        assertSameLinks(expectedLinks);
    }

    private void assertSameTOC(List<TOCLink> expectedLinks){
        List<TOCLink> actualLinks = getActualTocLinks();

        assertEquals(expectedLinks.size(), actualLinks.size());
        for(int numLink = 0 ; numLink < expectedLinks.size() ; numLink++ ){
            assertEquals(expectedLinks.get(numLink), actualLinks.get(numLink));
        }
    }

    private List<TOCLink> getActualTocLinks(){
        WebElement tocRoot = getDriver().findElement(By.id("dw__toc"));
        return getTocLevelLinks(tocRoot, 1);
    }

    private List<TOCLink> getTocLevelLinks(WebElement currentRoot, int nextLevel){
        List<TOCLink> tocLevelLinks = new ArrayList<>();
        for(WebElement nextLevelItem : currentRoot.findElements(By.className("level" + nextLevel))){
            WebElement link = nextLevelItem.findElement(By.xpath("./div/a"));
            tocLevelLinks.add(new TOCLink(nextLevel, link.getAttribute("href"), link.getAttribute("innerHTML")));
            tocLevelLinks.addAll(getTocLevelLinks(nextLevelItem, nextLevel+1));
        }
        return tocLevelLinks;
    }

    class TOCLink {
        private int level;
        private String target;
        private String text;

        public TOCLink(int level, String target, String text){
            this.level = level;
            this.target = target;
            this.text = text;
        }

        public int level(){return level;}
        public String target(){return target;}
        public String text(){return text;}

        @Override
        public boolean equals(Object obj) {
            if (obj == null) {return false;}
            if (obj.getClass() != this.getClass()) {return false;}
            final TOCLink other = (TOCLink) obj;
            return other.level == level && other.target.equals(target) && other.text.equals(text);
        }

        @Override
        public String toString(){
            return "TOCLink[" + level + ", " + target + ", " + text + "]";
        }
    }

    private static String addTitlesInOrderToHaveAToc(String pageContent){
        return "======A======\n"
                + "======B======\n"
                + "======C======\n"
                + pageContent;
    }
}
