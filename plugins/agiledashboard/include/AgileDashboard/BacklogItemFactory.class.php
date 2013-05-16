<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class AgileDashboard_BacklogItemFactory {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(AgileDashboard_BacklogItemDao $dao, Tracker_ArtifactFactory $artifact_factory, Tracker_FormElementFactory $form_element_factory) {
        $this->dao = $dao;
        $this->artifact_factory = $artifact_factory;
        $this->form_element_factory = $form_element_factory;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $redirect_paremeter = new Planning_MilestoneRedirectParameter();
        $redirect_to_self   = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_ContentPaneInfo::IDENTIFIER);
        
        $todo_collection = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        $done_collection = new AgileDashboard_Milestone_Pane_ContentRowPresenterCollection();
        $this->getMilestoneContent($user, $milestone, $todo_collection, $done_collection, $redirect_to_self);

        $backlog_tracker   = $milestone->getPlanning()->getBacklogTracker();
        $can_add_backlog_item_type = false;
        if ($backlog_tracker->userCanSubmitArtifact()) {
            $can_add_backlog_item_type = true;
        }
        return new AgileDashboard_Milestone_Pane_ContentPresenter(
            $todo_collection,
            $done_collection,
            $backlog_tracker->getName(),
            $can_add_backlog_item_type,
            $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($backlog_tracker).'&'.$redirect_to_self
        );
    }

    protected function getMilestoneContent(
        PFUser $user,
        Planning_ArtifactMilestone $milestone,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $todo_collection,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $done_collection,
        $redirect_to_self
    ) {
        $artifacts        = array();
        $backlog_item_ids = array();
        foreach ($this->getBacklogArtifacts($milestone) as $artifact) {
            $artifacts[$artifact->getId()] = $artifact;
            $backlog_item_ids[] = $artifact->getId();
        }
        $parents   = $this->getParentArtifacts($user, $milestone, $backlog_item_ids);
        $semantics = $this->getArtifactsSemantics($user, $milestone, $backlog_item_ids);
        foreach ($artifacts as $artifact) {
            $this->buildCollections($user, $todo_collection, $done_collection, $redirect_to_self, $artifact, $parents, $semantics);
        }
    }

    protected function getBacklogArtifacts(Planning_ArtifactMilestone $milestone) {
        return $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }

    private function getParentArtifacts(PFUser $user, Planning_ArtifactMilestone $milestone, array $backlog_item_ids) {
        $parents         = $this->artifact_factory->getParents($backlog_item_ids);
        $parent_tracker = $milestone->getPlanning()->getBacklogTracker()->getParent();
        if ($parent_tracker) {
            if ($this->userCanReadBacklogTitleField($user, $parent_tracker)) {
                $this->artifact_factory->setTitles($parents);
            } else {
                foreach ($parents as $artifact) {
                    $artifact->setTitle("");
                }
            }
        }
        return $parents;
    }

    private function getArtifactsSemantics(PFUser $user, Planning_ArtifactMilestone $milestone, array $backlog_item_ids) {
        $semantics = array();
        foreach ($this->dao->getArtifactsSemantics($backlog_item_ids, $this->getSemanticsTheUserCanSee($user, $milestone)) as $row) {
            $semantics[$row['id']] = array(
                Tracker_Semantic_Title::NAME  => $row[Tracker_Semantic_Title::NAME],
                Tracker_Semantic_Status::NAME => $row[Tracker_Semantic_Status::NAME],
            );
        }
        return $semantics;
    }

    private function getSemanticsTheUserCanSee(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        $semantics = array();
        if ($this->userCanReadBacklogTitleField($user, $backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Title::NAME;
        }
        if ($this->userCanReadBacklogStatusField($user, $backlog_tracker)) {
            $semantics[] = Tracker_Semantic_Status::NAME;
        }
        return $semantics;
    }

    protected function userCanReadBacklogTitleField(PFUser $user, Tracker $tracker) {
        return Tracker_Semantic_Title::load($tracker)->getField()->userCanRead($user);
    }

    protected function userCanReadBacklogStatusField(PFUser $user, Tracker $tracker) {
        return Tracker_Semantic_Status::load($tracker)->getField()->userCanRead($user);
    }

    protected function setRemainingEffort(PFUser $user, AgileDashboard_BacklogItem $backlog_item, Tracker_Artifact $artifact) {
        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $artifact->getTrackerId(),
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $user
        );
        if ($field) {
            $backlog_item->setRemainingEffort($field->fetchCardValue($artifact));
        }
    }

    private function buildCollections(
        PFUser $user,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $todo_collection,
        AgileDashboard_Milestone_Pane_ContentRowPresenterCollection $done_collection,
        $redirect_to_self,
        Tracker_Artifact $artifact,
        $parents,
        $semantics
    ) {
        $artifact_id = $artifact->getId();
        $artifact->setTitle($semantics[$artifact_id][Tracker_Semantic_Title::NAME]);

        $backlog_item = new AgileDashboard_BacklogItem($artifact, $redirect_to_self);
        if (isset($parents[$artifact_id])) {
            $backlog_item->setParent($parents[$artifact_id]);
        }
        if ($semantics[$artifact_id][Tracker_Semantic_Status::NAME] == AgileDashboard_BacklogItemDao::STATUS_OPEN) {
            $this->setRemainingEffort($user, $backlog_item, $artifact);
            $todo_collection->push($backlog_item);
        } else {
            $done_collection->push($backlog_item);
        }
    }
}

?>
