<template>
    <div class="container">
        <h1 class="title">Jira Task Dashboard</h1>

        <button class="btn btn-primary" @click="openCreateModal">
            Add Task
        </button>

        <!-- CREATE TASK MODAL -->
        <div
            v-if="showCreateModal"
            class="modal-backdrop"
            @click.self="closeCreateModal"
        >
            <div class="modal-content">
                <h5>Create New Task</h5>

                <input
                    v-model="newTask.summary"
                    placeholder="Task Summary"
                    class="form-control mb-2"
                />
                <textarea
                    v-model="newTask.description"
                    placeholder="Task Description"
                    class="form-control mb-2"
                ></textarea>

                <label>Assignee</label>
                <select v-model="newTask.assignee" class="form-control mb-2">
                    <option value="">Unassigned</option>
                    <option
                        v-for="user in users"
                        :key="user.accountId"
                        :value="user.accountId"
                    >
                        {{ user.displayName }}
                    </option>
                </select>

                <label>Priority</label>
                <select v-model="newTask.priority" class="form-control mb-2">
                    <option disabled value="">Select Priority</option>
                    <option v-for="p in priorities" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>

                <label>Status</label>
                <select v-model="newTask.status" class="form-control mb-2">
                    <option disabled value="">Select Status</option>
                    <option v-for="s in statuses" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </option>
                </select>

                <label>Sprint</label>
                <select v-model="selectedSprintId" class="form-control mb-2">
                    <option value="">No Sprint</option>
                    <option
                        v-for="sprint in sprints"
                        :key="sprint.id"
                        :value="sprint.id"
                    >
                        {{ sprint.name }}
                    </option>
                </select>

                <input
                    v-model="labelsInput"
                    placeholder="Labels (comma separated)"
                    class="form-control mb-2"
                />
                <button class="btn btn-primary" @click="createTask">
                    Save Changes
                </button>
                <button class="btn btn-secondary ml-2" @click="closeCreateModal">
                    Cancel
                </button>
            </div>

        </div>

        <!-- EDIT TASK MODAL -->
        <div
            v-if="showEditModal"
            class="modal-backdrop"
            @click.self="closeEditModal"
        >
            <div class="modal-content">
                <h5>Edit Task</h5>

                <input
                    v-model="editTask.fields.summary"
                    placeholder="Task Summary"
                    class="form-control mb-2"
                />
                <textarea
                    v-model="editDescription"
                    placeholder="Task Description"
                    class="form-control mb-2"
                ></textarea>

                <label>Assignee</label>
                <select
                    v-model="editTask.fields.assignee.accountId"
                    class="form-control mb-2"
                >
                    <option value="">Unassigned</option>
                    <option
                        v-for="user in users"
                        :key="user.accountId"
                        :value="user.accountId"
                    >
                        {{ user.displayName }}
                    </option>
                </select>

                <label>Priority</label>
                <select
                    v-model="editTask.fields.priority.id"
                    class="form-control mb-2"
                >
                    <option disabled value="">Select Priority</option>
                    <option v-for="p in priorities" :key="p.id" :value="p.id">
                        {{ p.name }}
                    </option>
                </select>

                <label>Status</label>
                <select
                    v-model="editTask.fields.status.id"
                    disabled
                    class="form-control mb-2"
                >
                    <!-- Status typically requires transitions, so disable editing for now -->
                    <option :value="editTask.fields.status.id">
                        {{ editTask.fields.status.name }}
                    </option>
                </select>

                <label>Sprint</label>
                <select
                    v-model="editSelectedSprintId"
                    class="form-control mb-2"
                >
                    <option value="">No Sprint</option>
                    <option
                        v-for="sprint in sprints"
                        :key="sprint.id"
                        :value="sprint.id"
                    >
                        {{ sprint.name }}
                    </option>
                </select>

                <input
                    v-model="editLabelsInput"
                    placeholder="Labels (comma separated)"
                    class="form-control mb-2"
                />

                <button class="btn btn-primary" @click="updateTask">
                    Save Changes
                </button>
                <button class="btn btn-secondary ml-2" @click="closeEditModal">
                    Cancel
                </button>
            </div>
        </div>

        <!-- TASK TABLE -->
        <table class="table mt-4" v-if="tasks.length">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Key</th>
                    <th>Summary</th>
                    <th>Assignee</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Labels</th>
                    <th>Sprint</th>
                    <th>Project</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="task in tasks" :key="task.id">
                    <td>{{ task.id }}</td>
                    <td>{{ task.key }}</td>
                    <td>{{ task.fields.summary }}</td>
                    <td>
                        {{ task.fields.assignee?.displayName || "Unassigned" }}
                    </td>
                    <td>{{ task.fields.status?.name || "N/A" }}</td>
                    <td>{{ task.fields.priority?.name || "N/A" }}</td>
                    <td>
                        <span v-if="task.fields.labels?.length">
                            <span
                                v-for="(label, i) in task.fields.labels"
                                :key="i"
                                class="badge"
                                >{{ label }}</span
                            >
                        </span>
                        <span v-else>-</span>
                    </td>
                    <td>{{ getSprintName(task.fields) }}</td>
                    <td>{{ task.fields.project?.name || "N/A" }}</td>
                    <td>
                        {{ new Date(task.fields.created).toLocaleString() }}
                    </td>
                    <td>
                        <button
                            class="btn btn-sm btn-info"
                            @click="openEditModal(task)"
                        >
                            Edit
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-else>No tasks found.</p>
    </div>
</template>

<script>
export default {
    data() {
        return {
            tasks: [],
            users: [],
            priorities: [],
            statuses: [],
            sprints: [],
            boards: [],
            selectedBoardId: null,
            selectedSprintId: "",
            editSelectedSprintId: "",
            showCreateModal: false,
            showEditModal: false,
            labelsInput: "",
            editLabelsInput: "",
            showEditModal: false,
            editTask: null,
            editDescription: "",
            editSprint: "",
            newTask: {
                summary: "",
                description: "",
                assignee: "",
                priority: "",
                status: "",
                sprint: "",
                labels: [],
            },
            editTask: null,
        };
    },
    created() {
        this.fetchTasks();
        this.fetchAssignableUsers();
        this.fetchPriorities();
        this.fetchStatuses();
        this.fetchBoards();
    },
    methods: {
        openEditModal(task) {
            this.editTask = JSON.parse(JSON.stringify(task)); // deep clone to avoid immediate binding
            this.editLabelsInput = (this.editTask.fields.labels || []).join(
                ", "
            );
            this.editDescription =
                this.editTask.fields.description?.content?.[0]?.content?.[0]
                    ?.text || "";
            const sprints = this.editTask.fields.customfield_10020;
            if (Array.isArray(sprints) && sprints.length) {
                const lastSprint = sprints[sprints.length - 1];
                if (typeof lastSprint === "string") {
                    const match = lastSprint.match(/id=(\d+)/);
                    this.editSelectedSprintId = match ? match[1] : "";
                } else if (typeof lastSprint === "object" && lastSprint.id) {
                    this.editSelectedSprintId = lastSprint.id;
                } else {
                    this.editSelectedSprintId = "";
                }
            } else {
                this.editSelectedSprintId = "";
            }

            this.showEditModal = true;
        },
        closeEditModal() {
            this.showEditModal = false;
            this.editTask = null;
            this.editLabelsInput = "";
            this.editDescription = "";
            this.editSprint = "";
        },
        fetchTasks() {
            this.$axios.get("/api/jira/tasks").then((res) => {
                this.tasks = res.data.issues || [];
            });
        },
        fetchAssignableUsers() {
            this.$axios.get("/api/jira/users").then((res) => {
                this.users = res.data;
            });
        },
        fetchPriorities() {
            this.$axios.get("/api/jira/priorities").then((res) => {
                this.priorities = res.data;
            });
        },
        fetchStatuses() {
            this.$axios.get("/api/jira/statuses").then((res) => {
                this.statuses = res.data;
            });
        },
        openCreateModal() {
            this.showCreateModal = true;
            this.resetNewTask();
        },
        closeCreateModal() {
            this.showCreateModal = false;
        },
        closeEditModal() {
            this.showEditModal = false;
            this.editTask = null;
            this.editLabelsInput = "";
        },
        resetNewTask() {
            this.newTask = {
                summary: "",
                description: "",
                assignee: "",
                priority: "",
                status: "",
                sprint: "",
                labels: [],
            };
            this.labelsInput = "";
        },
        createTask() {
            const payload = {
                summary: this.newTask.summary,
                description: this.newTask.description,
                labels: this.labelsInput
                    ? this.labelsInput.split(",").map((s) => s.trim())
                    : [],
                assignee: this.newTask.assignee
                    ? { accountId: this.newTask.assignee }
                    : null,
                priority: this.newTask.priority || null,
                status: this.newTask.status || null,
                sprint: this.selectedSprintId
                    ? Number(this.selectedSprintId)
                    : null,
            };

            this.$axios.post("/api/jira/tasks", payload).then((res) => {
                alert(`Task created: ${res.data.key}`);
                this.closeCreateModal();
                this.fetchTasks();
            });
        },
        getSprintName(fields) {
            const sprints = fields["customfield_10020"];
            if (Array.isArray(sprints) && sprints.length) {
                const lastSprint = sprints[sprints.length - 1];

                if (typeof lastSprint === "string" && lastSprint.match) {
                    const match = lastSprint.match(/name=([^,]*)/);
                    return match ? match[1] : "N/A";
                } else if (lastSprint && typeof lastSprint === "object") {
                    return lastSprint.name || "N/A";
                }
            }
            return "N/A";
        },
        updateTask() {
            if (!this.editTask || !this.editTask.key) {
                alert("No task selected to update.");
                return;
            }

            const payload = {
                summary: this.editTask.fields.summary,
                description: this.editDescription,
                labels: this.editLabelsInput
                    ? this.editLabelsInput.split(",").map((s) => s.trim())
                    : [],
                assignee: this.editTask.fields.assignee
                    ? { accountId: this.editTask.fields.assignee.accountId }
                    : null,
                priority: this.editTask.fields.priority?.id || null,
                sprint: this.selectedSprintId
                    ? Number(this.selectedSprintId)
                    : null,
            };

            this.$axios
                .put(`/api/jira/tasks/${this.editTask.key}`, payload)
                .then(() => {
                    alert("Task updated successfully");
                    this.closeEditModal();
                    this.fetchTasks();
                })
                .catch((err) => {
                    console.error(err);
                    alert("Failed to update task");
                });
        },
        fetchBoards() {
            this.$axios
                .get("/api/jira/boards")
                .then((res) => {
                    this.boards = res.data.values || [];
                    if (this.boards.length) {
                        this.selectedBoardId = this.boards[0].id;
                        this.fetchSprints(this.selectedBoardId);
                    }
                })
                .catch((err) => {
                    console.error("Failed to fetch boards", err);
                });
        },
        fetchSprints(boardId) {
            this.$axios
                .get("/api/jira/sprints", {
                    params: {
                        board_id: boardId,
                    },
                })
                .then((res) => {
                    this.sprints = res.data.values || [];
                })
                .catch((err) => {
                    console.error("Failed to fetch sprints", err);
                });
        },
    },
};
</script>

<style scoped>
.container {
    padding: 20px;
}
.title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
}
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}
.modal-content {
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}
.badge {
    display: inline-block;
    padding: 3px 8px;
    font-size: 12px;
    background-color: #e0e0e0;
    border-radius: 12px;
    margin-right: 4px;
    color: #333;
}
</style>
