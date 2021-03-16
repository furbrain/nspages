package nspages;

import org.junit.Test;

import java.util.ArrayList;
import java.util.List;

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

        // Assert the TOC doesn't have the links
        //TODO

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
        //TODO
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

    private static String addTitlesInOrderToHaveAToc(String pageContent){
        return "======A======\n"
                + "======B======\n"
                + "======C======\n"
                + pageContent;
    }
}
