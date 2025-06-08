<template>
  <div class="container">
    <h1 class="title">Jira Task Dashboard</h1>

    <button class="btn btn-primary" @click="openCreateModal">Add Task</button>

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
          <option v-for="sprint in sprints" :key="sprint.id" :value="sprint.id">
            {{ sprint.name }}
          </option>
        </select>

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

        <label>Assignee</label>
        <select
          class="form-control mb-2"
          :value="editTask.fields.assignee?.accountId || ''"
          @change="onEditAssigneeChange($event.target.value)"
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
        <select v-model="editTask.fields.priority.id" class="form-control mb-2">
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
          <option :value="editTask.fields.status.id">
            {{ editTask.fields.status.name }}
          </option>
        </select>

        <label>Sprint</label>
        <select v-model="editSelectedSprintId" class="form-control mb-2">
          <option value="">No Sprint</option>
          <option v-for="sprint in sprints" :key="sprint.id" :value="sprint.id">
            {{ sprint.name }}
          </option>
        </select>

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
          <td>{{ task.fields.assignee?.displayName || "Unassigned" }}</td>
          <td>{{ task.fields.status?.name || "N/A" }}</td>
          <td>{{ task.fields.priority?.name || "N/A" }}</td>
          <td>{{ getSprintName(task.fields) }}</td>
          <td>{{ task.fields.project?.name || "N/A" }}</td>
          <td>{{ new Date(task.fields.created).toLocaleString() }}</td>
          <td>
            <button class="btn btn-sm btn-info" @click="openEditModal(task)">
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
      editTask: null,
      editDescription: "",
      newTask: {
        summary: "",
        description: "",
        assignee: "",
        priority: "",
        status: "",
        sprint: "",
      },
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
    openCreateModal() {
      this.showCreateModal = true;
      this.resetNewTask();
    },
    closeCreateModal() {
      this.showCreateModal = false;
    },
    openEditModal(task) {
      this.editTask = JSON.parse(JSON.stringify(task));

      if (!this.editTask.fields.assignee) {
        this.editTask.fields.assignee = {};
      }

      this.editDescription =
        task.fields.description?.content?.[0]?.content?.[0]?.text || "";

      const sprints = task.fields.customfield_10020;
      if (Array.isArray(sprints) && sprints.length) {
        const lastSprint = sprints[sprints.length - 1];
        this.editSelectedSprintId =
          typeof lastSprint === "object"
            ? lastSprint.id
            : lastSprint.match(/id=(\d+)/)?.[1] || "";
      } else {
        this.editSelectedSprintId = "";
      }

      this.showEditModal = true;
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editTask = null;
      this.editDescription = "";
    },
    resetNewTask() {
      this.newTask = {
        summary: "",
        description: "",
        assignee: "",
        priority: "",
        status: "",
        sprint: "",
      };
    },
    onEditAssigneeChange(accountId) {
      if (!this.editTask.fields.assignee) {
        this.editTask.fields.assignee = {};
      }
      this.editTask.fields.assignee.accountId = accountId;
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
    fetchBoards() {
      this.$axios.get("/api/jira/boards").then((res) => {
        this.boards = res.data.values || [];
        if (this.boards.length) {
          this.selectedBoardId = this.boards[0].id;
          this.fetchSprints(this.selectedBoardId);
        }
      });
    },
    fetchSprints(boardId) {
      this.$axios
        .get("/api/jira/sprints", { params: { board_id: boardId } })
        .then((res) => {
          this.sprints = res.data.values || [];
        });
    },
    createTask() {
      const payload = {
        summary: this.newTask.summary,
        description: this.newTask.description,
        assignee: this.newTask.assignee
          ? { accountId: this.newTask.assignee }
          : null,
        priority: this.newTask.priority || null,
        status: this.newTask.status || null,
        sprint: this.selectedSprintId ? Number(this.selectedSprintId) : null,
      };

      this.$axios.post("/api/jira/tasks", payload).then((res) => {
        alert(`Task created: ${res.data.key}`);
        this.closeCreateModal();
        this.fetchTasks();
      });
    },
    updateTask() {
      if (!this.editTask?.key) {
        alert("No task selected to update.");
        return;
      }

      const payload = {
        summary: this.editTask.fields.summary,
        description: this.editDescription,
        assignee: this.editTask.fields.assignee?.accountId
          ? { accountId: this.editTask.fields.assignee.accountId }
          : null,
        priority: this.editTask.fields.priority?.id || null,
        sprint: this.editSelectedSprintId
          ? Number(this.editSelectedSprintId)
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
    getSprintName(fields) {
      const sprints = fields["customfield_10020"];
      if (Array.isArray(sprints) && sprints.length) {
        const lastSprint = sprints[sprints.length - 1];
        if (typeof lastSprint === "string") {
          const match = lastSprint.match(/name=([^,]*)/);
          return match ? match[1] : "N/A";
        } else if (typeof lastSprint === "object") {
          return lastSprint.name || "N/A";
        }
      }
      return "N/A";
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
</style>
