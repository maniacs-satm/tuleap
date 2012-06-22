<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'GitRepositoryFactory.class.php';
require_once 'common/system_event/SystemEventManager.class.php';
require_once 'common/project/ProjectManager.class.php';

class GitRepositoryManager {
    
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    
    /**
     * @var SystemEventManager 
     */
    private $system_event_manager;
    
    public function __construct(GitRepositoryFactory $repository_factory, SystemEventManager $system_event_manager) {
        $this->repository_factory   = $repository_factory;
        $this->system_event_manager = $system_event_manager;
    }
    
    public function deleteProjectRepositories(Project $project) {
        $repositories = $this->repository_factory->getAllRepositories($project);
        foreach ($repositories as $repository) {
            $repository->forceMarkAsDeleted();
            $this->system_event_manager->createEvent(
                'GIT_REPO_DELETE',
                 $project->getID().SystemEvent::PARAMETER_SEPARATOR.$repository->getId(),
                 SystemEvent::PRIORITY_MEDIUM
            );
        }
    }
}

?>
